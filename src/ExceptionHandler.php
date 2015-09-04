<?php

namespace G4\Tasker;

class ExceptionHandler
{
    private $taskId;

    /**
     * @var \G4\Tasker\Model\Domain\Task
     */
    private $taskData;

    /**
     * @var \Exception
     */
    private $exception;

    /**
     * @var \G4\Tasker\Model\Domain\TaskErrorLog
     */
    private $taskErrorLog;

    private $totalTime;

    public function __construct($taskId, \G4\Tasker\Model\Domain\Task $taskData, \Exception $exception, $totalTime)
    {
        $this->taskId    = $taskId;
        $this->taskData  = $taskData;
        $this->exception = $exception;
        $this->totalTime = $totalTime;
    }

    public function writeLog()
    {
        $this
            ->prepareLog()
            ->insert();
    }

    private function prepareLog()
    {
        $this->taskErrorLog = new \G4\Tasker\Model\Domain\TaskErrorLog();
        $this->taskErrorLog
            ->setTaskId($this->taskId)
            ->setIdentifier($this->taskData->getIdentifier())
            ->setTask($this->taskData->getTask())
            ->setData($this->taskData->getData())
            ->setTsStarted($this->taskData->getTsStarted())
            ->setDateStarted(date('c'))
            ->setExecTime($this->totalTime)
            ->setLog(json_encode([
                'file'    => $this->exception->getFile(),
                'message' => $this->exception->getMessage(),
                'line'    => $this->exception->getLine(),
                'code'    => $this->exception->getCode(),
            ]));
        return $this;
    }

    private function insert()
    {
        $mapper = new \G4\Tasker\Model\Mapper\Mysql\TaskErrorLog();
        $mapper->insert($this->taskErrorLog);
        return $this;
    }
}