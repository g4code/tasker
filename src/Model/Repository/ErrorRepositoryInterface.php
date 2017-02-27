<?php

namespace G4\Tasker\Model\Repository;


use G4\Tasker\Model\Domain\TaskErrorLog;

interface ErrorRepositoryInterface
{
    public function add(TaskErrorLog $taskError);
}