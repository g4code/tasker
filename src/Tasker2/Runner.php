<?php

namespace G4\Tasker\Tasker2;

use G4\Runner\Profiler;
use G4\Tasker\Model\Repository\TaskRepositoryInterface;
use G4\Tasker\TaskAbstract;
use G4\ValueObject\IntegerNumber;
use G4\ValueObject\StringLiteral;
use G4\ValueObject\Uuid;
use ND\NewRelic\NewRelicCacheInterface;
use ND\NewRelic\Transaction;
use PhpAmqpLib\Message\AMQPMessage;

class Runner extends \G4\Tasker\TimerAbstract
{
    const HTTP_X_ND_UUID = 'HTTP_X_ND_UUID';

    /**
     * @var \G4\ValueObject\Dictionary
     */
    private $taskData;

    private $resourceContainer;

    /**
     * @var \G4\Tasker\Model\Domain\Task
     */
    private $taskDomain;

    /**
     * @var \G4\Log\Logger
     */
    private $logger;

    /**
     * @var Transaction
     */
    private $newRelic;

    /**
     * @var NewRelicCacheInterface
     */
    private $newRelicCache;

    /**
     * @var \G4\Log\Data\TaskerExecution
     */
    private $taskerExecution;

    /**
     * @var TaskRepositoryInterface
     */
    private $taskRepository;

    /**
     * @var RetryAfterResolver
     */
    private $resolver;

    /**
     * @var Profiler
     */
    private $profiler;

    const LOG_TYPE = 'rb_worker';

    public function __construct(
        AMQPMessage $AMQPMessage,
        TaskRepositoryInterface $taskRepository,
        array $delayForRetries = [],
        string $queue = null
    ){
        $this->taskRepository = $taskRepository;
        $this->taskData = new \G4\ValueObject\Dictionary(
            json_decode($AMQPMessage->getBody(), true)
        );
        $this->taskData->add('queue_source', $queue);
        $this->taskDomain = \G4\Tasker\Model\Domain\Task::fromData($this->taskData->getAll());
        $this->taskerExecution = (new \G4\Log\Data\TaskerExecution())->setLogType(self::LOG_TYPE);
        $this->resolver = new RetryAfterResolver($delayForRetries);
    }

    public function setLogger(\G4\Log\Logger $logger = null)
    {
        $this->logger = $logger;
        return $this;
    }

    public function setNewRelic(Transaction $newRelic = null)
    {
        $this->newRelic = $newRelic;
        return $this;
    }

    public function setNewRelicCache(NewRelicCacheInterface $newRelicCache)
    {
        $this->newRelicCache = $newRelicCache;
        return $this;
    }

    public function setProfiler(Profiler $profiler)
    {
        $this->profiler = $profiler;
        return $this;
    }

    public function clearProfilerData()
    {
        $this->profiler && $this->profiler->clearProfilers();
    }

    public function registerProfilerTicker(\G4\Profiler\Ticker\TickerAbstract $profiler)
    {
        if (!$this->profiler instanceof Profiler) {
            return $this;
        }
        $this->profiler->addProfiler($profiler);
        return $this;
    }

    public function execute()
    {
        $this->timerStart();

        $this->taskDomain
            ->setStatusWorking()
            ->setIdentifier(gethostname())
            ->setTsStarted(microtime(true));

        $this->logTaskStart();
        $this->logTaskExecution(true);
        $this->logNewRelicStart();

        $task = $this->getTaskInstance();
        $task->setEncodedData($this->taskDomain->getData());
        if ($this->hasResourceContainer()) {
            $this->setResourceContainer($this->resourceContainer);
        }
        $this->setRequestUuid();

        try {
            ob_start();

            $this->checkMaxRetryAttempts();

            $this->taskDomain->setStartedCount($this->taskDomain->getStartedCount() + 1);

            $task->execute();
            $this->taskerExecution->setOutput(ob_get_flush());
            $this->logNewRelicEnd();
        } catch (\Throwable $e) {
            $this->taskerExecution->setOutput(ob_get_flush());
            $this->handleException($e);
            $this->clearProfilerData();
            throw $e;
        }

        $this->timerStop();
        $this->taskDomain->setStatusDone($this->getTotalTime());
        $this->logTaskExecution();
        $this->clearProfilerData();
    }

    /**
     * @return TaskAbstract
     */
    private function getTaskInstance()
    {
        $className = $this->taskDomain->getTask();
        $taskInstance = new $className;

        if (!$taskInstance instanceof TaskAbstract) {
            throw new \RuntimeException(
                sprintf("Class '%s' must extend \\G4\\Tasker\\TaskAbstract class", $this->taskDomain->getTask())
            );
        }

        return $taskInstance;
    }

    public function getResourceContainer()
    {
        if ($this->hasResourceContainer()) {
            return $this->resourceContainer;
        }
        throw new \RuntimeException('Resource container is missing');
    }

    public function hasResourceContainer()
    {
        return $this->resourceContainer !== null;
    }

    public function setResourceContainer($resourceContainer)
    {
        $this->resourceContainer = $resourceContainer;
        return $this;
    }

    private function setRequestUuid()
    {
        $_SERVER[self::HTTP_X_ND_UUID] = $this->taskDomain->getRequestUuid() !== null
            ? $this->taskDomain->getRequestUuid()
            : Uuid::generate();

        return $this;
    }

    private function logTaskStart()
    {
        $this->taskerExecution
            ->setId(md5(uniqid(microtime(), true)))
            ->setTask($this->taskDomain);
        return $this;
    }

    private function logTaskExecution($start = false)
    {
        $start
            ? $this->taskerExecution->taskStarted()
            : $this->taskerExecution->taskFinished();

        if ($start && $this->profiler) {
            $this->taskerExecution->setProfiler($this->profiler);
        }

        if ($this->logger !== null) {
            $this->logger->log($this->taskerExecution);
            $start === false && $this->logger->clearData();
        }

        return $this;
    }

    private function logNewRelicStart()
    {
        if ($this->newRelic !== null) {
            $this->newRelic->startTransaction(new StringLiteral($this->taskDomain->getTask()));
            $this->addCustomNewRelicParams();
        }
        return $this;
    }

    private function addCustomNewRelicParams()
    {
        if ($this->newRelicCache instanceof NewRelicCacheInterface) {
            $userId = $this->getUserIdFromData($this->taskDomain->getData());

            if ($userId && $this->newRelicCache->exists(new IntegerNumber((int) $userId))) {
                $cacheData = $this->newRelicCache->find(new IntegerNumber((int) $userId));
                $this->newRelic->addCustomParams($cacheData);
            }
        }
    }

    public function getUserIdFromData($taskData)
    {
        $data = json_decode(str_replace("\n", "\\n", trim($taskData)), true);
        return isset($data['user_id']) ? $data['user_id'] : null;
    }

    private function logNewRelicEnd()
    {
        if ($this->newRelic !== null) {
            $this->newRelic->endTransaction();
        }
        return $this;
    }

    private function logNewRelicFailed(\Throwable $exception)
    {
        if ($this->newRelic !== null) {
            $this->newRelic->failedTransaction($exception);
        }
        return $this;
    }

    private function checkMaxRetryAttempts()
    {
        $maxRetryAttempts = $this->resolver->getMaxRetryAttempts();

        if ($this->taskDomain->getStartedCount() > $maxRetryAttempts) {
            throw new \G4\Tasker\Model\Exception\RetryFailedException(
                sprintf('Task with task_id=%s failed miserably with started_count=%s greater than MAX_RETRY_ATTEMPTS=%s.',
                    $this->taskDomain->getTaskId(),
                    $this->taskDomain->getStartedCount(),
                    $maxRetryAttempts)
            );
        }
        return $this;
    }

    public function handleException(\Throwable $e)
    {
        if (!$this->taskDomain instanceof \G4\Tasker\Model\Domain\Task) {
            return $this;
        }

        $this->timerStop();

        $throwException = false;
        switch ($e) {
            case($e instanceof \G4\Tasker\Model\Exception\CompletedNotDoneException):
                $this->taskDomain->setStatusCompletedNotDone($this->getTotalTime());
                break;
            case($e instanceof \G4\Tasker\Model\Exception\WaitingForRetryException):
                $this->taskDomain->setStatusWaitingForRetry($this->getTotalTime());
                break;
            case($e instanceof \G4\Tasker\Model\Exception\RetryFailedException):
                $this->taskDomain->setStatusRetryFailed($this->getTotalTime());
                $throwException = true;
                break;
            default:
                $this->taskDomain->setStatusBroken($this->getTotalTime());
                $throwException = true;
                break;
        }
        $this->taskerExecution->setException($e);
        $this->logTaskExecution();
        $this->logNewRelicFailed($e);
        $this->logNewRelicEnd();

        if ($this->taskDomain->getStatus() === \G4\Tasker\Consts::STATUS_WAITING_FOR_RETRY) {
            $this->requeueTask();
        }

        if ($throwException) {
            throw $e;
        }

        return $this;
    }

    private function requeueTask()
    {
        $this->taskDomain
            ->setTsStarted(0)
            ->setExecTime(-1)
            ->setTsCreated(time() + $this->resolver
                    ->resolve($this->taskDomain->getStartedCount()))
            ->setTaskId(null)
            ->setStatus(\G4\Tasker\Consts::STATUS_PENDING);
        $this->taskRepository->add($this->taskDomain);
        return $this;
    }

}
