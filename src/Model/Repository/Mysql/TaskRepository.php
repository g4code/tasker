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
        $this->pdo->exec('SET NAMES utf8');
    }

    public function find($id)
    {
        if (!is_int($id)) {
            throw new \RuntimeException(sprintf('Task id=%s is not integer', $id));
        }

        $query = 'SELECT * FROM tasks WHERE task_id=:id';
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

        $query = 'SELECT * FROM tasks WHERE identifier=:identifier AND status=:status AND ts_created <= :ts_created ORDER BY ts_created ASC, priority DESC LIMIT :limit';

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
        $query = 'SELECT * FROM tasks WHERE status=:status AND ts_started<=:ts_started ORDER BY ts_started ASC, priority DESC LIMIT :limit';

        $stmt = $this->pdo->prepare($query);
        $stmt->bindValue(':status', $status, \PDO::PARAM_INT);
        $stmt->bindValue(':ts_started', time() - $olderThanSeconds, \PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);

        $stmt->execute();

        return array_map(function($data) {
            return \G4\Tasker\Model\Domain\Task::fromData($data);
        }, $stmt->fetchAll());
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
        $query = 'INSERT INTO tasks (recu_id, identifier, task, `data`, request_uuid, status, priority, ts_created, ts_started, exec_time, started_count)
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

        $sql = 'INSERT INTO tasks (recu_id, identifier, task, data, request_uuid, status, priority, ts_created, ts_started, exec_time, started_count) VALUES ';
        $sql .= implode(', ', $insertQuery);

        $stmt = $this->pdo->prepare($sql);
        $this->execute($stmt,$insertData);
    }

    public function update(Task $task)
    {
        $query = 'UPDATE tasks SET recu_id=:recu_id, identifier=:identifier, task=:task, `data`=:data, 
request_uuid=:request_uuid, status=:status, priority=:priority, ts_created=:ts_created, ts_started=:ts_started, 
exec_time=:exec_time, started_count=:started_count WHERE task_id=:task_id';

        $stmt = $this->pdo->prepare($query);
        $stmt = $this->prepareFields($stmt, $task);

        $stmt->bindValue(':task_id',       $task->getTaskId(),       \PDO::PARAM_INT);

        return $this->execute($stmt);
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