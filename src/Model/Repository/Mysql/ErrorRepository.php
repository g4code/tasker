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
        $query = '';

        $this->pdo->exec();
    }
}