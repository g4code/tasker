<?php
namespace G4\Tasker;

declare(ticks = 1);

use G4\Tasker\Model\Exception\RetryFailedException;
use G4\Tasker\Model\Repository\ErrorRepositoryInterface;
use G4\Tasker\Model\Repository\TaskRepositoryInterface;

class Runner extends TimerAbstract
{
    const MAX_RETRY_ATTEMPTS = 3;

    /**
     * @var \G4\Tasker\Model\Domain\Task
     */
    private $taskData;

    /**
     * @var int
     */
    private $taskId;

    /**
     * @var ErrorRepositoryInterface
     */
    private $errorRepository;

    /**
     * @var TaskRepositoryInterface
     */
    private $taskRepository;


    /**
     * @param TaskRepositoryInterface $taskRepository
     * @param ErrorRepositoryInterface $errorRepository
     */
    public function __construct(TaskRepositoryInterface $taskRepository, ErrorRepositoryInterface $errorRepository)
    {
        $this->taskRepository = $taskRepository;
        $this->timerStart();
        $this->errorRepository = $errorRepository;

        register_shutdown_function([$this, 'handleShutdownError']);
        set_error_handler([$this, 'handleError']);
    }

    public function getTaskId()
    {
        if(null === $this->taskId) {
            throw new \RuntimeException('Task ID is not set');
        }
        return $this->taskId;
    }

    public function setTaskId($value)
    {
        $this->taskId = (int) $value;
        return $this;
    }

    public function execute()
    {
        try {
            $this
                ->fetchTaskData()
                ->updateToWorking()
                ->checkMaxRetryAttempts()
                ->executeTask()
                ->timerStop()
                ->updateToDone();

        } catch (\Exception $e) {
            print "Exception " . $e->getMessage();
            $this->handleException($e);
            throw $e;
        }
    }

    public function setMultiWorking()
    {
        $this
            ->fetchTaskData()
            ->updateToMultiWorking();
    }

    /**
     * @return Runner
     */
    private function executeTask()
    {
        $this->getTaskInstance()
            ->setEncodedData($this->taskData->getData())
            ->execute();
        return $this;
    }

    /**
     * @return Runner
     * @throws \Exception
     */
    private function fetchTaskData()
    {
        $this->taskData = $this->taskRepository->find($this->getTaskId());

        return $this;
    }

    private function checkMaxRetryAttempts()
    {
        if ($this->taskData->getStartedCount() > self::MAX_RETRY_ATTEMPTS) {
            print $this->taskData->getStartedCount();
            throw new RetryFailedException(
                sprintf('Task with task_id=%s failed miserably with started_count=%s greater than MAX_RETRY_ATTEMPTS=%s.',
                $this->taskData->getTaskId(),
                $this->taskData->getStartedCount(),
                self::MAX_RETRY_ATTEMPTS)
            );
        }
        return $this;
    }

    /**
     * @throws \Exception
     * @return TaskAbstract
     */
    private function getTaskInstance()
    {
        $className = $this->taskData->getTask();

        if (class_exists($className) === false) {
            throw new \RuntimeException(sprintf("Class '%s' for task not found", $className));
        }

        $task = new $className;

        if (!$task instanceof TaskAbstract) {
            throw new \RuntimeException(sprintf("Class '%s' must extend \\G4\\Tasker\\TaskAbstract class", $className));
        }

        return $task;
    }

    /**
     * @return Runner
     */
    private function updateToCompletedNotDone()
    {
        $this->taskData->setStatusCompletedNotDone($this->getTotalTime());
        $this->taskRepository->update($this->taskData);
        return $this;
    }

    /**
     * @return Runner
     */
    private function updateToDone()
    {
        $this->taskData->setStatusDone($this->getTotalTime());
        $this->taskRepository->update($this->taskData);
        return $this;
    }

    /**
     * @return Runner
     */
    private function updateToWaitingForRetry()
    {
        $this->taskData->setStatusWaitingForRetry($this->getTotalTime());
        $this->taskRepository->update($this->taskData);
        return $this;
    }

    private function updateToRetryFailed()
    {
        $this->taskData->setStatusRetryFailed();
        $this->taskRepository->update($this->taskData);
        return $this;
    }

    /**
     * @return Runner
     */
    private function updateToWorking()
    {
        $this->taskData
            ->setStatusWorking()
            ->setTsStarted(time())
            ->setStartedCount($this->taskData->getStartedCount() + 1);
        $this->taskRepository->update($this->taskData);
        return $this;
    }

    private function updateToMultiWorking()
    {
        $this->taskData
            ->setStatusMultiWorking()
            ->setTsStarted(time());
        $this->taskRepository->update($this->taskData);
        return $this;
    }

    private function updateToBroken()
    {
        $this->taskData->setStatusBroken($this->getTotalTime());
        $this->taskRepository->update($this->taskData);
        return $this;
    }

    /**
     * @todo: Dejan: remove duplicated code, uses the same logic as fetchTaskData()
     * @return boolean
     */
    private function checkIsTaskFinished()
    {
        $taskData = $this->taskRepository->find($this->getTaskId());
        return $taskData->isDone();
    }

    public function handleException(\Exception $e)
    {
        // because register_shutdown_function is registered multiple times inside MultiRunner, it is also called
        // multiple times in case of FATAL error. So this condition will ensure that only currently executing/failed
        // task will be updated to STATUS_BROKEN

        if (!$this->taskData instanceof \G4\Tasker\Model\Domain\Task) {
            return $this;
        }

        if (!$this->taskData->isWorking()) {
            return $this;
        }

        $this
            ->timerStop();

        switch($e) {
            case($e instanceof \G4\Tasker\Model\Exception\CompletedNotDoneException):
                $this->updateToCompletedNotDone();
                break;
            case($e instanceof \G4\Tasker\Model\Exception\WaitingForRetryException):
                $this->updateToWaitingForRetry();
                break;
            case($e instanceof \G4\Tasker\Model\Exception\RetryFailedException):
                $this->updateToRetryFailed();
                break;
            default:
                $this->updateToBroken();
        }

        $eh = new \G4\Tasker\ExceptionHandler($this->taskData, $e, $this->getTotalTime(), $this->errorRepository);
        $eh->writeLog();
        return $this;
    }

    public function handleShutdownError()
    {
        $error = error_get_last();

        if (!in_array($error['type'], [ E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
            return $this;
        }

        $exception = new \ErrorException($error['message'], $error['type'], 1, $error['file'], $error['line']);
        $this->handleException($exception);
        return false;
    }

    public function handleError($errno, $errstr, $errfile, $errline)
    {
        $exception = new \ErrorException($errstr, $errno, 0, $errfile, $errline);
        $this->handleException($exception);
        return false;
    }
}