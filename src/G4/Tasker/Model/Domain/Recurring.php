<?php
namespace G4\Tasker\Model\Domain;

use G3\Model\Domain\DomainAbstract;

class Recurring extends DomainAbstract
{
    protected static $_idKey = 'recu_id';

    protected $_task;

    protected $_frequency;

    protected $_data;

    protected $_status;

    public function getRawData()
    {
        return array(
            self::getIdKey() => $this->getId(),
            'task'           => $this->getTask(),
            'frequency'      => $this->getFrequency(),
            'data'           => $this->getData(),
            'status'         => $this->getStatus(),
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
     * @return int
     */
    public function getFrequency()
    {
        return $this->_frequency;
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
     * @return G4\Tasker\Model\Domain\Recurring
     */
    public function setTask($value)
    {
        $this->_task = $value;
        return $this;
    }

    /**
     * @return G4\Tasker\Model\Domain\Recurring
     */
    public function setFrequency($value)
    {
        $this->_frequency = $value;
        return $this;
    }

    /**
     * @return G4\Tasker\Model\Domain\Recurring
     */
    public function setData($value)
    {
        $this->_data = $value;
        return $this;
    }

    /**
     * @return G4\Tasker\Model\Domain\Recurring
     */
    public function setStatus($value)
    {
        $this->_status = $value;
        return $this;
    }

}
