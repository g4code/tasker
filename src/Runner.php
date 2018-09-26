<?php
namespace G4\Tasker;

declare(ticks = 1);

use G4\Log\Data\TaskerExecution;
use G4\Tasker\Model\Exception\RetryFailedException;
use G4\Tasker\Model\Repository\ErrorRepositoryInterface;
use G4\Tasker\Model\Repository\TaskRepositoryInterface;
use G4\ValueObject\Uuid;

class Runner extends TimerAbstract
{
    const MAX_RETRY_ATTEMPTS = 3;

    const HTTP_X_ND_UUID = 'HTTP_X_ND_UUID';

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

    private $resourceContainer;

    /**
     * @var \G4\Log\Logger
     */
    private $logger;

    /**
     * @var \G4\Log\Data\TaskerExecution
     */
    private $taskerExecution;


    /**
     * @param TaskRepositoryInterface $taskRepository
     * @param ErrorRepositoryInterface $errorRepository
     */
    public function __construct(TaskRepositoryInterface $taskRepository, ErrorRepositoryInterface $errorRepository)
    {
        $this->taskRepository = $taskRepository;
        $this->timerStart();
        $this->errorRepository = $errorRepository;
        $this->taskerExecution = new \G4\Log\Data\TaskerExecution();

        register_shutdown_function([$this, 'handleShutdownError']);
        set_error_handler([$this, 'handleError']);
    }

    public function setLogger(\G4\Log\Logger $logger=null)
    {
        $this->logger = $logger;
        return $this;
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

    public function getResourceContainer()
    {
        if($this->hasResourceContainer()){
            return $this->resourceContainer;
        }
        throw new \Exception('Resource container is missing');
    }

    public function hasResourceContainer()
    {
        return $this->resourceContainer != null;
    }

    public function setResourceContainer($resourceContainer)
    {
        $this->resourceContainer = $resourceContainer;
        return $this;
    }

    public function execute()
    {
        try {
            $this
                ->fetchTaskData()
                ->logTaskStart()
                ->updateToWorking()
                ->checkMaxRetryAttempts()
                ->executeTask()
                ->timerStop()
                ->updateToDone()
                ->logTaskExecution();
        } catch (\Exception $e) {
            $this->handleException($e);
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
        $aTask = $this->getTaskInstance();
        $aTask->setEncodedData($this->taskData->getData());
        if($this->hasResourceContainer()){
            $aTask->setResourceContainer($this->resourceContainer);
        }
        $this->setRequestUuid();
        $aTask->execute();
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

    private function logTaskStart()
    {
        $this->taskerExecution
            ->setId(md5(uniqid(microtime(), true)))
            ->setTask($this->taskData);
        $this->logTaskExecution();
        return $this;
    }

    private function checkMaxRetryAttempts()
    {
        if ($this->taskData->getStartedCount() > self::MAX_RETRY_ATTEMPTS) {
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

        if (!$this->taskData->isWorking() && !$this->taskData->isBroken()) {
            return $this;
        }

        $this
            ->timerStop();

        $throwException = false;
        switch($e) {
            case($e instanceof \G4\Tasker\Model\Exception\CompletedNotDoneException):
                $this->updateToCompletedNotDone();
                break;
            case($e instanceof \G4\Tasker\Model\Exception\WaitingForRetryException):
                $this->updateToWaitingForRetry();
                break;
            case($e instanceof \G4\Tasker\Model\Exception\RetryFailedException):
                $this->updateToRetryFailed();
                $throwException = true;
                break;
            default:
                $this->updateToBroken();
                $throwException = true;
                break;
        }
        $this->taskerExecution->setException($e);
        $this->logTaskExecution();

        $eh = new \G4\Tasker\ExceptionHandler($this->taskData, $e, $this->getTotalTime(), $this->errorRepository);
        $eh->writeLog();

        if ($throwException) {
            throw $e;
        }

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

    private function setRequestUuId()
    {
        $this->taskData->getRequestUuid() !== null
            ? $_SERVER[self::HTTP_X_ND_UUID] = $this->taskData->getRequestUuid()
            : Uuid::generate();

        return $this;
    }

    private function logTaskExecution()
    {
        $this->logger !== null && $this->logger->log($this->taskerExecution);
    }
}