<?php
namespace G4\Tasker;

use G4\Tasker\Model\Mapper\Mysql\Task as TaskMapper;
use G4\Tasker\Model\Domain\Task as TaskDomain;

abstract class TaskAbstract
{
    private $_createdTs;

    private $_data;

    private $_meta;

    private $_priority;

    public function addDelay($value)
    {
        $this->_createdTs = $this->getTsCreated() + $value;
        return $this;
    }

    public function getData()
    {
        return $this->_data;
    }

    public function getTsCreated()
    {
        return empty($this->_createdTs)
            ? time()
            : $this->_createdTs;
    }

    public function getEncodedData()
    {
        return $this->_data !== null
            ? json_encode($this->_data)
            : '';
    }

    public function getName()
    {
        return str_replace("\\", '\\\\', get_class($this));
    }

    public function getPriority()
    {
        return $this->_priority !== null
                    ? $this->_priority
                    : Consts::PRIORITY_MEDIUM;
    }

    public function setData(array $value)
    {
        $this->_verifyData($value);
        $this->_data = $value;
        return $this;
    }

    public function setEncodedData($value)
    {
        $this->_data = json_decode($value, true);
        return $this;
    }

    public function setPriority($value)
    {
        $this->_priority = $value;
        return $this;
    }

    abstract public function execute();

    protected function _addMeta($key, $required = false, $valid = null, $default = null)
    {
        if(!is_string($key) || empty($key)) {
            throw new \Exception('Meta key must be non empty string');
        }

        if(isset($this->_meta[$key])) {
            throw new \Exception('Meta key already declared');
        }

        $this->_meta[$key] = array(
            'required' => (bool) $required,
            'valid'    => $valid,
            'default'  => $default,
        );

        return $this;
    }

    protected function _verifyData($data)
    {
        if(empty($data)) {
            throw new \Exception('If data is set, it must be non empty array');
        }

        // if meta is not set, or is set to empty array, return true since we don't have anything to verify
        if(null === $this->_meta || (is_array($this->_meta) && empty($this->_meta))) {
            return true;
        }

        foreach ($this->_meta as $key => $value) {
            if($value['required']) {
                if(!isset($data[$key]) || empty($data[$key])) {
                    $class = get_called_class();
                    throw new \Exception("Task '{$class}' requires '{$key}' to be set");
                }
            }
        }

        return true;
    }
}