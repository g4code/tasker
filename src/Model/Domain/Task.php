<?php
namespace G4\Tasker\Model\Domain;

use G4\DataMapper\Domain\DomainAbstract;

class Task extends DomainAbstract
{
    protected static $_idKey = 'task_id';

    protected $_recu_id;

    protected $_task;

    protected $_data;

    protected $_status;

    protected $_priority;

    protected $_created_ts;

    protected $_exec_time;

    public function getRawData()
    {
        return array(
            self::getIdKey() => $this->getId(),
            'recu_id'        => $this->getRecurringId(),
            'task'           => $this->getTask(),
            'data'           => $this->getData(),
            'status'         => $this->getStatus(),
            'priority'       => $this->getPriority(),
            'created_ts'     => $this->getCreatedTs(),
            'exec_time'      => $this->getExecTime(),
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
    public function getCreatedTs()
    {
        return $this->_created_ts;
    }

    /**
     * @return int
     */
    public function getExecTime()
    {
        return $this->_exec_time;
    }

    /**
     * @return G4\Tasker\Model\Domain\Task
     */
    public function setTask($value)
    {
        $this->_task = $value;
        return $this;
    }

    /**
     * @return G4\Tasker\Model\Domain\Task
     */
    public function setRecurringId($value)
    {
        $this->_recu_id = $value;
        return $this;
    }

    /**
     * @return G4\Tasker\Model\Domain\Task
     */
    public function setData($value)
    {
        $this->_data = $value;
        return $this;
    }

    /**
     * @return G4\Tasker\Model\Domain\Task
     */
    public function setStatus($value)
    {
        $this->_status = $value;
        return $this;
    }

    /**
     * @return G4\Tasker\Model\Domain\Task
     */
    public function setPriority($value)
    {
        $this->_priority = $value;
        return $this;
    }

    /**
     * @return G4\Tasker\Model\Domain\Task
     */
    public function setCreatedTs($value)
    {
        $this->_created_ts = $value;
        return $this;
    }

    /**
     * @return G4\Tasker\Model\Domain\Task
     */
    public function setExecTime($value)
    {
        $this->_exec_time = $value;
        return $this;
    }



}
