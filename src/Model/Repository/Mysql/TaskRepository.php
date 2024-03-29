<?php

namespace G4\Tasker\Model\Repository\Mysql;

use G4\Tasker\Consts;
use G4\Tasker\Model\Domain\Task;
use G4\Tasker\Model\Repository\TaskRepositoryInterface;

class TaskRepository implements TaskRepositoryInterface
{
    const MULTI_WORKING_OLDER_THAN = 300;   // 5 minutes
    const MULTI_WORKING_LIMIT = 100;        // how many tasks to reset to STATUS_PENDING

    const RESET_TASKS_AFTER_SECONDS  = 60;  // seconds to retry failed tasks
    const RESET_TASKS_LIMIT = 20;           // how many tasks to reset at once

    const REBALANCE_LIMIT = 1000;           // how many tasks to rebalance at once
    const REBALANCE_TIME_IN_FUTURE = 3600;  // seconds in future tasks to rebalance

    const DELETE_TASKS_OLDER_THAN = 86400 * 2; // 2 days
    const DELETE_TASKS_LIMIT = 2000;


    /**
     * @var \PDO
     */
    private $pdo;

    /**
     * @var string
     */
    private $identifier;

    public function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function find($id)
    {
        if (!is_int($id)) {
            throw new \RuntimeException(sprintf('Task id=%s is not integer', $id));
        }

        $query = 'SELECT * FROM '. Consts::TASKS_TABLE_NAME .' WHERE task_id=:id';
        $stmt = $this->pdo->prepare($query);
        $stmt->execute([
            'id' => $id
        ]);
        $data = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$data) {
            throw new \RuntimeException(sprintf('Task with id=%s not found', $id));
        }

        return \G4\Tasker\Model\Domain\Task::fromData($data);
    }


    public function findReserved($limit)
    {
        $limit = (int) $limit;
        if (!$limit) {
            throw new \RuntimeException('Limit is not valid');
        }

        $query = 'SELECT * FROM '. Consts::TASKS_TABLE_NAME .' WHERE identifier=:identifier AND status=:status AND ts_created <= :ts_created ORDER BY ts_created ASC, priority DESC LIMIT :limit';

        $stmt = $this->pdo->prepare($query);

        $stmt->bindValue(':identifier', $this->getIdentifier());
        $stmt->bindValue(':status', Consts::STATUS_PENDING, \PDO::PARAM_INT);
        $stmt->bindValue(':ts_created', time(), \PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);

        $stmt->execute();

        return array_map(function($data) {
            return \G4\Tasker\Model\Domain\Task::fromData($data);
        }, $stmt->fetchAll());
    }

    public function findOldMultiWorking()
    {
        return $this->fetchTasks(Consts::STATUS_MULTI_WORKING, self::MULTI_WORKING_OLDER_THAN, self::MULTI_WORKING_LIMIT);
    }

    public function findWaitingForRetry()
    {
        return $this->fetchTasks(Consts::STATUS_WAITING_FOR_RETRY, self::RESET_TASKS_AFTER_SECONDS, self::RESET_TASKS_LIMIT);
    }

    private function fetchTasks($status, $olderThanSeconds, $limit)
    {
        $query = 'SELECT * FROM '. Consts::TASKS_TABLE_NAME .' WHERE status=:status AND ts_started<=:ts_started ORDER BY ts_started ASC, priority DESC LIMIT :limit';

        $stmt = $this->pdo->prepare($query);
        $stmt->bindValue(':status', $status, \PDO::PARAM_INT);
        $stmt->bindValue(':ts_started', time() - $olderThanSeconds, \PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);

        $stmt->execute();

        return array_map(function($data) {
            return \G4\Tasker\Model\Domain\Task::fromData($data);
        }, $stmt->fetchAll());
    }

    /**
     * @param array $availableHostnames
     * @return array|int[]
     */
    public function findTasksForRebalance(array $availableHostnames)
    {
        $query = sprintf('SELECT task_id FROM %s
               WHERE
                   identifier NOT IN ("%s") AND status=:status AND ts_created <= :ts_created
               ORDER BY ts_created ASC LIMIT :limit',
            Consts::TASKS_TABLE_NAME,
            implode('","', $availableHostnames)
        );

        $stmt = $this->pdo->prepare($query);
        $stmt->bindValue(':status', Consts::STATUS_PENDING, \PDO::PARAM_INT);
        $stmt->bindValue(':limit', self::REBALANCE_LIMIT, \PDO::PARAM_INT);
        $stmt->bindValue(':ts_created', time() + self::REBALANCE_TIME_IN_FUTURE, \PDO::PARAM_INT);

        $stmt->execute();

        return $stmt->fetchAll(\PDO::FETCH_COLUMN);
    }

    private function getIdentifier()
    {
        if ($this->identifier === null) {
            $this->generateIdentifier();
        }
        return $this->identifier;
    }

    private function generateIdentifier()
    {
        $this->identifier = gethostname();
        return $this;
    }

    public function add(Task $task)
    {
        $query = 'INSERT INTO ' . Consts::TASKS_TABLE_NAME . ' (recu_id, identifier, task, `data`, request_uuid, status, priority, ts_created, ts_started, exec_time, started_count)
VALUES(:recu_id, :identifier, :task, :data, :request_uuid, :status, :priority, :ts_created, :ts_started, :exec_time, :started_count)';


        $stmt = $this->pdo->prepare($query);
        $stmt = $this->prepareFields($stmt, $task);

        $this->execute($stmt);
    }

    /**
     * @param Task[] $tasks
     */
    public function addBulk($tasks)
    {
        if (count($tasks) === 0) {
            return;
        }

        $insertQuery = [];
        $insertData = [];

        foreach ($tasks as $task) {
            $insertQuery[] = '(?,?,?,?,?,?,?,?,?,?,?)';
            $insertData[] = $task->getRecurringId();
            $insertData[] = $task->getIdentifier();
            $insertData[] = $task->getTask();
            $insertData[] = $task->getData();
            $insertData[] = $task->getRequestUuid();
            $insertData[] = $task->getStatus();
            $insertData[] = $task->getPriority();
            $insertData[] = $task->getTsCreated();
            $insertData[] = $task->getTsStarted();
            $insertData[] = $task->getExecTime();
            $insertData[] = $task->getStartedCount();
        }

        $sql = 'INSERT INTO ' . Consts::TASKS_TABLE_NAME . ' (recu_id, identifier, task, data, request_uuid, status, priority, ts_created, ts_started, exec_time, started_count) VALUES ';
        $sql .= implode(', ', $insertQuery);

        $stmt = $this->pdo->prepare($sql);
        $this->execute($stmt,$insertData);
    }

    public function update(Task $task)
    {
        $query = 'UPDATE '. Consts::TASKS_TABLE_NAME .' SET recu_id=:recu_id, identifier=:identifier, task=:task, `data`=:data, 
request_uuid=:request_uuid, status=:status, priority=:priority, ts_created=:ts_created, ts_started=:ts_started, 
exec_time=:exec_time, started_count=:started_count WHERE task_id=:task_id';

        $stmt = $this->pdo->prepare($query);
        $stmt = $this->prepareFields($stmt, $task);

        $stmt->bindValue(':task_id',       $task->getTaskId(),       \PDO::PARAM_INT);

        return $this->execute($stmt);
    }

    public function updateStatus($status, Task ...$tasks)
    {
        if (count($tasks) === 0) {
            return 0;
        }

        $taskIds = count($tasks) === 1
            ? [$tasks[0]->getTaskId()]
            : array_map(function (Task $task) {
                return $task->getTaskId();
            }, $tasks);

        $query= 'UPDATE '. Consts::TASKS_TABLE_NAME .' SET status=%d WHERE task_id IN (%s)';
        $this->pdo->query(
            sprintf($query, $status, implode(',', $taskIds))
        );
    }

    /**
     * @param string$identifier
     * @param array $taskIds
     * @return void
     */
    public function updateIdentifier($identifier, array $taskIds)
    {
        if (count($taskIds) === 0) {
            return;
        }

        $query= sprintf(
            'UPDATE %s SET identifier="%s" WHERE task_id IN (%s)',
            Consts::TASKS_TABLE_NAME,
            $identifier,
            implode(',', $taskIds)
        );

        $this->pdo->exec($query);
    }

    /**
     * @param \PDOStatement $stmt
     * @param Task $task
     * @return \PDOStatement
     */
    private function prepareFields(\PDOStatement $stmt, Task $task)
    {

        $stmt->bindValue(':recu_id',       $task->getRecurringId(),  \PDO::PARAM_INT);
        $stmt->bindValue(':identifier',    $task->getIdentifier());
        $stmt->bindValue(':task',          $task->getTask());
        $stmt->bindValue(':data',          $task->getData());
        $stmt->bindValue(':request_uuid',  $task->getRequestUuid());
        $stmt->bindValue(':status',        $task->getStatus(),       \PDO::PARAM_INT);
        $stmt->bindValue(':priority',      $task->getPriority(),     \PDO::PARAM_INT);
        $stmt->bindValue(':ts_created',    $task->getTsCreated(),    \PDO::PARAM_INT);
        $stmt->bindValue(':ts_started',    $task->getTsStarted(),    \PDO::PARAM_INT);
        $stmt->bindValue(':exec_time',     $task->getExecTime());
        $stmt->bindValue(':started_count', $task->getStartedCount(), \PDO::PARAM_INT);

        return $stmt;
    }

    /**
     * @param int $olderThan
     * @param int $limit
     * @return void
     */
    public function deleteProcessedTasks(
        $olderThan = self::DELETE_TASKS_OLDER_THAN,
        $limit = self::DELETE_TASKS_LIMIT
    ) {
        $query = sprintf(
            'DELETE FROM %s
                  WHERE status IN (:status_done, :status_sent_to_queue) AND ts_created <= :ts_created
                  ORDER BY ts_created ASC LIMIT :limit',
            Consts::TASKS_TABLE_NAME
        );

        $stmt = $this->pdo->prepare($query);
        $stmt->bindValue(':status_done', Consts::STATUS_DONE, \PDO::PARAM_INT);
        $stmt->bindValue(':status_sent_to_queue', Consts::STATUS_SENT_TO_QUEUE_FOR_EXECUTION, \PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->bindValue(':ts_created', time() - $olderThan, \PDO::PARAM_INT);

        $stmt->execute();
    }

    private function execute(\PDOStatement $stmt, $data = null)
    {
        $inTransaction = $this->pdo->inTransaction();
        if (!$inTransaction) {
            $this->pdo->beginTransaction();
        }
        $res = $stmt->execute($data);
        if (!$inTransaction) {
            $this->pdo->commit();
        }
        return $res;
    }
}
