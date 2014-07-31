<?php
namespace G4\Tasker;

use \G4\Tasker\Model\Mapper\Mysql\Task as TaskMapper;

class Cleaner
{
    /**
     *
     * @var integer
     */
    private $maxRetryAttempts;

    /**
     *
     * @var G4\Tasker\Model\Mapper\Mysql\Task
     */
    private $taskMapper;

    /**
     *
     * @var integer
     */
    private $timeDelay;

    public function __construct()
    {
        $this->taskMapper = new TaskMapper();
    }

    public function run()
    {
        $this->taskMapper
            ->setTimeDelay($this->timeDelay)
            ->setRetryFailedStatus($this->maxRetryAttempts)
            ->resetTaskStatusPendingWithIdentifier()
            ->resetTaskStatusWorking();
    }

    public function setMaxRetryAttempts($value)
    {
        $this->maxRetryAttempts = $value;
        return $this;
    }

    public function setTimeDelay($value)
    {
        $this->timeDelay = $value;
        return $this;
    }
}