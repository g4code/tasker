<?php

namespace G4\Tasker\Model\Repository\Mysql;


use G4\Tasker\Consts;
use G4\Tasker\Model\Domain\Task;
use G4\Tasker\Model\Repository\TaskRepositoryInterface;

class TaskRepository implements TaskRepositoryInterface
{
    const MULTI_WORKING_OLDER_THAN = 600;   // 10 minutes
    const MULTI_WORKING_LIMIT = 20;         // how many tasks to reset to STATUS_PENDING

    /**
     * @var \PDO
     */
    private $pdo;

    public function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
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


    public function getReservedTasks($limit)
    {
        $limit = (int) $limit;
        if (!$limit) {
            throw new \RuntimeException('Limit is not valid');
        }

        $query = 'SELECT * FROM tasks WHERE identifier=:identifier AND status=:status AND ts_created <= :ts_created AND started_count=0 LIMIT :limit';

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

    public function getOldMultiWorkingTasks()
    {
        $query = 'SELECT * FROM tasks WHERE status=:status AND ts_started<=:ts_started LIMIT :limit';

        $stmt = $this->pdo->prepare($query);
        $stmt->bindValue(':status', Consts::STATUS_MULTI_WORKING, \PDO::PARAM_INT);
        $stmt->bindValue(':ts_started', time() - self::MULTI_WORKING_OLDER_THAN, \PDO::PARAM_INT);
        $stmt->bindValue(':limit', self::MULTI_WORKING_LIMIT, \PDO::PARAM_INT);

        $stmt->execute();

        return array_map(function($data) {
            return \G4\Tasker\Model\Domain\Task::fromData($data);
        }, $stmt->fetchAll());
    }

    private function getIdentifier()
    {
        if (!isset($this->_identifier)) {
            $this->_generateIdentifier();
        }
        return $this->_identifier;
    }

    private function _generateIdentifier()
    {
        $this->_identifier = gethostname();
        return $this;
    }

    public function add(Task $task)
    {
        $query = 'INSERT INTO tasks (recu_id, identifier, task, `data`, status, priority, ts_created, ts_started, exec_time, started_count)
VALUES(:recu_id, :identifier, :task, :data, :status, :priority, :ts_created, :ts_started, :exec_time, :started_count)';

        $stmt = $this->pdo->prepare($query);
        $stmt = $this->prepareFields($stmt, $task);

        $stmt->execute();

    }

    public function update(Task $task)
    {
        $update = [];
        foreach ($task->getRawData() as $col => $val) {
            $update[] = sprintf('%s="%s"', $col, $this->pdo->quote($val));
        }

        $query = 'UPDATE tasks SET recu_id=:recu_id, identifier=:identifier, task=:task, `data`=:data, 
status=:status, priority=:priority, ts_created=:ts_created, ts_started=:ts_started, exec_time=:exec_time,
started_count=:started_count WHERE task_id=:task_id';

        $stmt = $this->pdo->prepare($query);
        $stmt = $this->prepareFields($stmt, $task);

        $stmt->bindValue(':task_id',       $task->getTaskId(),       \PDO::PARAM_INT);

        return $stmt->execute();
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
        $stmt->bindValue(':status',        $task->getStatus(),       \PDO::PARAM_INT);
        $stmt->bindValue(':priority',      $task->getPriority(),     \PDO::PARAM_INT);
        $stmt->bindValue(':ts_created',    $task->getTsCreated(),    \PDO::PARAM_INT);
        $stmt->bindValue(':ts_started',    $task->getTsStarted(),    \PDO::PARAM_INT);
        $stmt->bindValue(':exec_time',     $task->getExecTime());
        $stmt->bindValue(':started_count', $task->getStartedCount(), \PDO::PARAM_INT);

        return $stmt;
    }
}