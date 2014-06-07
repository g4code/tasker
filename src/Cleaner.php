<?php
namespace G4\Tasker;

use \G4\Tasker\Model\Mapper\Mysql\Task as TaskMapper;

class Cleaner
{
    /**
     *
     * @var integer
     */
    private $_maxRetryAttempts;

    /**
     *
     * @var G4\Tasker\Model\Mapper\Mysql\Task
     */
    private $_taskMapper;

    /**
     *
     * @var integer
     */
    private $_timeDelay;

    public function __construct()
    {
        $this->_taskMapper = new TaskMapper();
    }

    public function run()
    {
        $this->_taskMapper
            ->setTimeDelay($this->_timeDelay)
            ->setRetryFailedStatus($this->_maxRetryAttempts)
            ->resetTaskStatusPendingWithIdentifier()
            ->resetTaskStatusWorking();
    }

    public function setMaxRetryAttempts($value)
    {
        $this->_maxRetryAttempts = $value;
        return $this;
    }

    public function setTimeDelay($value)
    {
        $this->_timeDelay = $value;
        return $this;
    }
}