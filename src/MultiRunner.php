<?php
namespace G4\Tasker;

class MultiRunner
{
    private $taskIds;

    public function execute()
    {
        foreach ($this->taskIds as $taskId) {
            $runner = new Runner();
            $runner
                ->setTaskId($taskId)
                ->execute();
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