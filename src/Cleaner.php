<?php
namespace G4\Tasker;

use \G4\Tasker\Model\Mapper\Mysql\Task as TaskMapper;

class Cleaner
{
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
            ->resetTaskStatusPendingWithIdentifier($this->_timeDelay)
            ->resetTaskStatusWorking($this->_timeDelay);
    }

    public function setTimeDelay($value)
    {
        $this->_timeDelay = $value;
        return $this;
    }
}