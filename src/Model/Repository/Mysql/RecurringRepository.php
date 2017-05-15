<?php

namespace G4\Tasker\Model\Repository\Mysql;

use G4\Tasker\Model\Repository\RecurringRepositoryInterface;

class RecurringRepository implements RecurringRepositoryInterface
{
    /**
     * @var \PDO
     */
    private $pdo;

    public function __construct(\PDO $db)
    {
        $this->pdo = $db;
        $this->pdo->exec('SET NAMES utf8');
    }

    public function getNextTasks()
    {
        $query = 'SELECT * FROM tasks_recurrings
WHERE status = :status_recu
AND recu_id NOT IN (SELECT DISTINCT tasks.recu_id FROM tasks WHERE status = :status)';

        $stmt = $this->pdo->prepare($query);
        $stmt->bindValue(':status_recu', \G4\Tasker\Consts::RECURRING_TASK_STATUS_ACTIVE, \PDO::PARAM_INT);
        $stmt->bindValue(':status',      \G4\Tasker\Consts::STATUS_PENDING,               \PDO::PARAM_INT);
        $stmt->execute();

        return array_map(function($data) {
            return \G4\Tasker\Model\Domain\Recurring::fromData($data);
        }, $stmt->fetchAll());
    }
}