<?php

namespace G4\Tasker\Model\Domain;

use G4\DataMapper\Domain\DomainAbstract;

class TaskErrorLog extends DomainAbstract
{

    protected static $_idKey = 'tel_id';

    protected $_task_id;

    protected $_identifier;

    protected $_task;

    protected $_data;

    protected $_ts_started;

    protected $_date_started;

    protected $_exec_time;

    protected $_log;

    public function getRawData()
    {
        return array(
            self::getIdKey() => $this->getId(),
            'task_id'        => $this->getTaskId(),
            'identifier'     => $this->getIdentifier(),
            'task'           => $this->getTask(),
            'data'           => $this->getData(),
            'ts_started'     => $this->getTsStarted(),
            'date_started'   => $this->getDateStarted(),
            'exec_time'      => $this->getExecTime(),
            'log'            => $this->getLog(),
        );
    }

    public function getTaskId()
    {
        return $this->_task_id;
    }

    public function getIdentifier()
    {
        return $this->_identifier;
    }

    public function getTask()
    {
        return $this->_task;
    }

    public function getData()
    {
        return $this->_data;
    }

    public function getTsStarted()
    {
        return $this->_ts_started;
    }

    public function getDateStarted()
    {
        return $this->_date_started;
    }

    public function getExecTime()
    {
        return $this->_exec_time;
    }

    public function getLog()
    {
        return json_decode($this->_log, true);
    }

    public function setTaskId($task_id)
    {
        $this->_task_id = $task_id;
        return $this;
    }

    public function setIdentifier($identifier)
    {
        $this->_identifier = $identifier;
        return $this;
    }

    public function setTask($task)
    {
        $this->_task = $task;
        return $this;
    }

    public function setData($data)
    {
        $this->_data = $data;
        return $this;
    }

    public function setTsStarted($ts_started)
    {
        $this->_ts_started = $ts_started;
        return $this;
    }

    public function setDateStarted($date_started)
    {
        $this->_date_started = $date_started;
        return $this;
    }

    public function setExecTime($exec_time)
    {
        $this->_exec_time = $exec_time;
        return $this;
    }

    public function setLog($log)
    {
        $this->_log = json_encode($log);
        return $this;
    }


}