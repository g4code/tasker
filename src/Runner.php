<?php
namespace G4\Tasker;

use G4\Tasker\Model\Mapper\Mysql\Task as TaskMapper;

class Runner extends TimerAbstract
{
    private $_taskMapper;

    private $_taskId;

    private $_taskData;


    public function __construct()
    {
        $this->_timerStart();
        $this->_taskMapper = new TaskMapper;
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
        try {
            $this
                ->_fetchTaskData()
                ->_updateToWorking()
                ->_executeTask()
                ->_timerStop()
                ->_updateToDone();

        } catch (\Exception $e) {
            // log message here
        }
    }

    /**
     * @return \G4\Tasker\Runner
     */
    private function _executeTask()
    {
        $this->_getTaskInstance()
            ->setEncodedData($this->_taskData->getData())
            ->execute();
        return $this;
    }

    /**
     * @return \G4\Tasker\Runner
     */
    private function _fetchTaskData()
    {
        $identity = $this->_taskMapper->getIdentity();

        $identity
            ->field('task_id')
            ->eq($this->getTaskId());

        $this->_taskData = $this->_taskMapper->findOne($identity);
        return $this;
    }

    /**
     * @throws \Exception
     * @return \G4\Tasker\TaskAbstract
     */
    private function _getTaskInstance()
    {
        $className = $this->_taskData->getTask();

        if (class_exists($className) === false) {
            throw new \Exception("Class '{$className}' for task not found");
        }

        $task = new $className;

        if (!$task instanceof \G4\Tasker\TaskAbstract) {
            throw new \Exception("Class '{$className}' must extend \G4\Tasker\TaskAbstract class");
        }

        return $task;
    }

    /**
     * @return \G4\Tasker\Runner
     */
    private function _updateToDone()
    {
        $this->_taskData
            ->setStatus(Consts::STATUS_DONE)
            ->setExecTime($this->_getTotalTime());
        $this->_taskMapper->update($this->_taskData);
        return $this;
    }

    /**
     * @return \G4\Tasker\Runner
     */
    private function _updateToWorking()
    {
        $this->_taskData
            ->setStatus(Consts::STATUS_WORKING)
            ->setStartedTime(time());
        $this->_taskMapper->update($this->_taskData);
        return $this;
    }
}