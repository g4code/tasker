<?php
namespace G4\Tasker;

use G4\Tasker\Model\Mapper\Mysql\Task as TaskMapper;

class Runner
{
    private $_taskId;

    private $_taskData;

    public function getTaskId()
    {
        if(null === $this->_runner) {
            throw new \Exception('Task ID is not set');
        }
        return $this->_taskId;
    }

    public function setTaskId($value)
    {
        $this->_taskId = $value;
        return $this;
    }

    public function execute()
    {
        try {

            $this->_fetchTaskData();

            $className = $this->_taskData->getName();

            if(class_exists($className) === false) {
                throw new \Exception("Class '{$className}' for task not found");
            }

            $task = new $className;

            if( ! $task instanceof \G4\Tasker\TaskAbstract) {
                throw new \Exception("Class '{$className}' must extend \G4\Tasker\TaskAbstract class");
            }

            $result = $task
                ->setData($this->_taskData->getData())
                ->execute();

        } catch (\Exception $e) {
            // log message here
            return false;
        }

        return true;
    }

    private function _fetchTaskData()
    {
        $mapper = new TaskMapper();

        $identity = $mapper->getIdentity();

        $identity
            ->field('task_id')
            ->eq($this->getTaskId());

        $this->_taskData = $mapper->findOne($identity);
    }
}