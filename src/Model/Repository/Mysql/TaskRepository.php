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
        $stmt->execute(
            [
                ':identifier' => $this->getIdentifier(),
                ':status'     => Consts::STATUS_MULTI_WORKING,
                ':ts_created' => time() - self::MULTI_WORKING_OLDER_THAN,
                ':limit'      => $limit,
            ]
        );

        return $stmt->fetchAll();
    }

    public function getOldMultiWorkingTasks()
    {
        $query = 'SELECT * FROM tasks WHERE status=:status AND ts_started<=:ts_started LIMIT :limit';

        $stmt = $this->pdo->prepare($query);
        $stmt->execute(
            [
                ':status'     => Consts::STATUS_MULTI_WORKING,
                ':ts_started' => time() - self::MULTI_WORKING_OLDER_THAN,
                ':limit'      => self::MULTI_WORKING_LIMIT,
            ]
        );

        return $stmt->fetchAll();
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

    public function update(Task $task)
    {
        $update = [];
        foreach ($task->getRawData() as $col => $val) {
            $update[] = sprintf('%s="%s"', $col, $this->pdo->quote($val));
        }

        $query = 'UPDATE tasks SET ' . implode(',', $update) . ' WHERE task_id=:id';

        $this->pdo->exec($query);
    }
}