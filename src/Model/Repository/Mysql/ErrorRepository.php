<?php

namespace G4\Tasker\Model\Repository\Mysql;

use G4\Tasker\Model\Domain\TaskErrorLog;
use G4\Tasker\Model\Repository\ErrorRepositoryInterface;

class ErrorRepository implements ErrorRepositoryInterface
{

    /**
     * @var \PDO
     */
    private $pdo;

    public function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function add(TaskErrorLog $taskError)
    {
        $query = 'INSERT INTO tasks_error_log (task_id, identifier, task, data, ts_started, date_started, exec_time, log) VALUES (:task_id, :identifier, :task, :data, :ts_started, :date_started, :exec_time, :log)';

        $stmt = $this->pdo->prepare($query);

        $stmt->bindValue(':task_id',      $taskError->getTaskId(), \PDO::PARAM_INT);
        $stmt->bindValue(':identifier',   $taskError->getIdentifier());
        $stmt->bindValue(':task',         $taskError->getTask());
        $stmt->bindValue(':data',         $taskError->getData());
        $stmt->bindValue(':ts_started',   $taskError->getTsStarted(), \PDO::PARAM_INT);
        $stmt->bindValue(':date_started', $taskError->getDateStarted());
        $stmt->bindValue(':exec_time',    $taskError->getExecTime());
        $stmt->bindValue(':log',          json_encode($taskError->getLog()));

        if ($this->pdo->inTransaction()) {
            $stmt->execute();
            return;
        }

        $this->pdo->beginTransaction();
        $stmt->execute();
        $this->pdo->commit();
    }
}