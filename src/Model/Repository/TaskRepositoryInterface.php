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
     * @param $limit
     * @return array|Task[]
     */
    public function findReserved($limit);

    /**
     * @return array|Task[]
     */
    public function findOldMultiWorking();

    /**
     * @return array|Task[]
     */
    public function findWaitingForRetry();

    public function add(Task $task);
    public function update(Task $task);
    public function updateStatus($status, Task ...$tasks);

    /**
     * @param int $olderThan
     * @param int $limit
     * @return void
     */
    public function deleteProcessedTasks($olderThan, $limit);
}