<?php
namespace G4\Tasker\Model\Domain;

use G4\DataMapper\Domain\DomainAbstract;

class Task extends DomainAbstract
{
    protected static $_idKey = 'task_id';

    protected $_recu_id;

    protected $_identifier;

    protected $_task;

    protected $_data;

    protected $_status;

    protected $_priority;

    protected $_ts_created;

    protected $_ts_started;

    protected $_exec_time;

    protected $_started_count;

    public function getRawData()
    {
        return array(
            self::getIdKey() => $this->getId(),
            'recu_id'        => $this->getRecurringId(),
            'identifier'     => $this->getIdentifier(),
            'task'           => $this->getTask(),
            'data'           => $this->getData(),
            'status'         => $this->getStatus(),
            'priority'       => $this->getPriority(),
            'ts_created'     => $this->getTsCreated(),
            'ts_started'     => $this->getTsStarted(),
            'exec_time'      => $this->getExecTime(),
            'started_count'  => $this->getStartedCount(),
        );
    }

    /**
     * @return string
     */
    public function getTask()
    {
        return $this->_task;
    }

    /**
     * @return string
     */
    public function getIdentifier()
    {
        return $this->_identifier;
    }

    /**
     * @return string
     */
    public function getRecurringId()
    {
        return $this->_recu_id;
    }

    /**
     * @return int
     */
    public function getData()
    {
        return $this->_data;
    }

    /**
     * @return int
     */
    public function getStatus()
    {
        return $this->_status;
    }

    /**
     * @return int
     */
    public function getPriority()
    {
        return $this->_priority;
    }

    /**
     * @return int
     */
    public function getTsCreated()
    {
        return $this->_ts_created;
    }

    /**
     * @return int
     */
    public function getTsStarted()
    {
        return $this->_ts_started;
    }

    /**
     * @return int
     */
    public function getExecTime()
    {
        return $this->_exec_time;
    }

    /**
     * @return int
     */
    public function getStartedCount()
    {
        return $this->_started_count;
    }

    /**
     * @return \G4\Tasker\Model\Domain\Task
     */
    public function setTask($value)
    {
        $this->_task = $value;
        return $this;
    }

    /**
     * @return \G4\Tasker\Model\Domain\Task
     */
    public function setIdentifier($value)
    {
        $this->_identifier = $value;
        return $this;
    }

    /**
     * @return \G4\Tasker\Model\Domain\Task
     */
    public function setRecurringId($value)
    {
        $this->_recu_id = $value;
        return $this;
    }

    /**
     * @return \G4\Tasker\Model\Domain\Task
     */
    public function setData($value)
    {
        $this->_data = $value;
        return $this;
    }

    /**
     * @return \G4\Tasker\Model\Domain\Task
     */
    public function setStatus($value)
    {
        $this->_status = $value;
        return $this;
    }

    /**
     * @return \G4\Tasker\Model\Domain\Task
     */
    public function setPriority($value)
    {
        $this->_priority = $value;
        return $this;
    }

    /**
     * @return \G4\Tasker\Model\Domain\Task
     */
    public function setTsCreated($value)
    {
        $this->_ts_created = $value;
        return $this;
    }

    /**
     * @return \G4\Tasker\Model\Domain\Task
     */
    public function setTsStarted($value)
    {
        $this->_ts_started = $value;
        return $this;
    }

    /**
     * @return \G4\Tasker\Model\Domain\Task
     */
    public function setExecTime($value)
    {
        $this->_exec_time = $value;
        return $this;
    }

    /**
     * @return \G4\Tasker\Model\Domain\Task
     */
    public function setStartedCount($value)
    {
        $this->_started_count = $value;
        return $this;
    }

}
