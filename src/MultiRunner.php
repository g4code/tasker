<?php
namespace G4\Tasker;

class MultiRunner
{
    private $taskIds;

    public function execute()
    {
        $tasks = [];

        foreach ($this->taskIds as $taskId) {
            $runner = new Runner();
            $runner
                ->setTaskId($taskId)
                ->setMultiWorking();

            $tasks[] = $runner;
        }

        foreach ($tasks as $task) {
            $task->execute();
        }
    }

    public function setTaskIds($value)
    {
        $this->taskIds = is_array($value)
            ? $value
            : json_decode($value);
        return $this;
    }
}