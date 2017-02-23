<?php

namespace G4\Tasker\Model\Repository;


use G4\Tasker\Model\Domain\Task;

interface TaskRepositoryInterface
{
    /**
     * @param $taskId
     * @return Task
     */
    public function find($taskId);
    /**
     * @param $taskId
     * @return array|Task[]
     */
    public function getReservedTasks($limit);
    /**
     * @param $taskId
     * @return array|Task[]
     */
    public function getOldMultiWorkingTasks();

    public function add(Task $task);
    public function update(Task $task);
}