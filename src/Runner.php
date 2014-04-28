<?php
namespace G4\Tasker;

use G4\Tasker\Model\Mapper\Mysql\Task as TaskMapper;

class Runner extends TimerAbstract
{
    private $_taskId;

    private $_taskData;

    public function __construct()
    {
        $this->_timerStart();
    }

    public function getTaskId()
    {
        if(null === $this->_taskId) {
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
        $mapper = new TaskMapper;

        try {

            $this->_fetchTaskData();

            $className = $this->_taskData->getTask();

            $this->_taskData
                ->setStartedTime(time());

            $mapper->update($this->_taskData);

            if(class_exists($className) === false) {
                throw new \Exception("Class '{$className}' for task not found");
            }

            $task = new $className;

            if( ! $task instanceof \G4\Tasker\TaskAbstract) {
                throw new \Exception("Class '{$className}' must extend \G4\Tasker\TaskAbstract class");
            }

            $result = $task
                ->setEncodedData($this->_taskData->getData())
                ->execute();

            $this->_timerStop();

            $status = Consts::STATUS_DONE;

        } catch (\Exception $e) {
            $status = Consts::STATUS_BROKEN;

            // log message here
        }

        $this->_taskData
            ->setStatus($status)
            ->setExecTime($this->_getTotalTime());

        $mapper->update($this->_taskData);
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