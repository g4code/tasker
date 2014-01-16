<?php
namespace G4\Tasker;

use G4\Tasker\Model\Mapper\Mysql\Task as TaskMapper;
use G4\Tasker\Model\Domain\Task as TaskDomain;

abstract class TaskAbstract
{
    private $_meta;

    private $_data;

    public function getData()
    {
        return $this->_data;
    }

    public function setData($value)
    {
        $this->_data = json_decode($value, true);
        return $this;
    }

    abstract public function execute();

    protected function _addMeta($key, $required = false, $valid = null, $default = null)
    {
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

    public function addToQueue(array $data = null, $priority = Consts::PRIORITY_MEDIUM)
    {
        $this->_verifyData($data);

        $priority = intval($priority);
        if(!$priority) {
            $priority = Consts::PRIORITY_MEDIUM;
        }

        $this->_save(get_called_class(), $data, $priority);
    }

    protected function _verifyData($data)
    {
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

    private function _save($task, array $data = null, $priority = Consts::PRIORITY_MEDIUM)
    {
        if(!is_string($task)) {
            throw new \Exception('Task name must be string');
        }

        if(null !== $data && (!is_array($data) || empty($data)) ) {
            throw new \Exception('If data is set, it must be non empty array');
        }

        $priority = intval($priority);
        if(!$priority) {
            $priority = Consts::PRIORITY_MEDIUM;
        }

        $parsedData = null !== $data
            ? json_encode($data)
            : '';

        $taskMapper = new TaskMapper();

        $domain = new TaskDomain();
        $domain
            ->setRecurringId(0)
            ->setTask($task)
            ->setData($parsedData)
            ->setStatus(Consts::STATUS_PENDING)
            ->setPriority($priority)
            ->setCreatedTs(time())
            ->setExecTime(0)
            ->setMapper($taskMapper)
            ->save();
    }
}