<?php

namespace G4\Tasker;

use G4\Tasker\Model\Domain\TaskErrorLog;

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

    private $exceptionMapper;

    public function __construct(\G4\Tasker\Model\Domain\Task $taskData, \Exception $exception, $totalTime, \G4\Tasker\Model\Mapper\Mysql\TaskErrorLog $exceptionMapper)
    {
        $this->taskData  = $taskData;
        $this->exception = $exception;
        $this->totalTime = $totalTime;
        $this->exceptionMapper = $exceptionMapper;
    }

    public function writeLog()
    {
        $this
            ->prepareLog()
            ->insert();
    }

    private function prepareLog()
    {
        $log = json_encode([
            'file'    => $this->exception->getFile(),
            'message' => $this->exception->getMessage(),
            'line'    => $this->exception->getLine(),
            'code'    => $this->exception->getCode(),
        ]);


        $this->taskErrorLog = TaskErrorLog::fromTask(
            $this->taskData,
            date('c'),
            $this->totalTime,
            $log
        );
/*
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
*/
        return $this;
    }

    private function insert()
    {
        $this->exceptionMapper->add($this->taskErrorLog);
        return $this;
    }
}