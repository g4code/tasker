<?php

namespace G4\Tasker\Model\Repository\Mysql;

use G4\Tasker\Model\Repository\RecurringRepositoryInterface;
use G4\Tasker\Consts;

class RecurringRepository implements RecurringRepositoryInterface
{
    /**
     * @var \PDO
     */
    private $pdo;

    public function __construct(\PDO $db)
    {
        $this->pdo = $db;
    }

    public function getNextTasks()
    {
        $query = sprintf(
            'SELECT * FROM %s 
            WHERE status=%d 
            AND recu_id NOT IN (SELECT DISTINCT recu_id FROM %s WHERE status=%d AND recu_id > 0)',
            Consts::RECURRING_TASKS_TABLE_NAME,
            Consts::RECURRING_TASK_STATUS_ACTIVE,
            Consts::TASKS_TABLE_NAME,
            Consts::STATUS_PENDING
        );

        $stmt = $this->pdo->query($query);

        return array_map(function($data) {
            return \G4\Tasker\Model\Domain\Recurring::fromData($data);
        }, $stmt->fetchAll());
    }
}