<?php

namespace G4\Tasker;

use G4\Tasker\Model\Domain\Task;
use G4\Tasker\Model\Domain\TaskErrorLog;
use G4\Tasker\Model\Repository\ErrorRepositoryInterface;

class ExceptionHandler
{
    /**
     * @var Task
     */
    private $taskData;

    /**
     * @var \Exception
     */
    private $exception;

    /**
     * @var TaskErrorLog
     */
    private $taskErrorLog;

    private $totalTime;

    /**
     * @var ErrorRepositoryInterface
     */
    private $errorRepository;

    public function __construct(Task $taskData, \Exception $exception, $totalTime, ErrorRepositoryInterface $errorRepository)
    {
        $this->taskData  = $taskData;
        $this->exception = $exception;
        $this->totalTime = $totalTime;
        $this->errorRepository = $errorRepository;
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
        return $this;
    }

    private function insert()
    {
        $this->errorRepository->add($this->taskErrorLog);
        return $this;
    }
}