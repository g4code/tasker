<?php

namespace G4\Tasker\Model\Repository;


use G4\Tasker\Model\Domain\Task;

interface TaskRepositoryInterface
{
    public function find($taskId);
    public function getReservedTasks($limit);
    public function getOldMultiWorkingTasks();
    public function update(Task $task);
}