<?php

namespace G4\Tasker\Tasker2;

use G4\Tasker\TaskAbstract;
use G4\ValueObject\StringLiteral;
use G4\ValueObject\Uuid;
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
     * @var \ND\NewRelic\Transaction
     */
    private $newRelic;

    /**
     * @var \G4\Log\Data\TaskerExecution
     */
    private $taskerExecution;

    const LOG_TYPE = 'rb_worker';

    public function __construct(AMQPMessage $AMQPMessage)
    {
        $this->taskData = new \G4\ValueObject\Dictionary(
            json_decode($AMQPMessage->getBody(), true)
        );
        $this->taskDomain = \G4\Tasker\Model\Domain\Task::fromData($this->taskData->getAll());
        $this->taskerExecution = new \G4\Log\Data\TaskerExecution();
        $this->taskerExecution->setLogType(self::LOG_TYPE);
    }

    public function setLogger(\G4\Log\Logger $logger = null)
    {
        $this->logger = $logger;
        return $this;
    }

    public function setNewRelic(\ND\NewRelic\Transaction $newRelic = null)
    {
        $this->newRelic = $newRelic;
        return $this;
    }

    public function execute()
    {
        // todo check max retry
        // todo handle waiting for retry, etc...

        $this->timerStart();

        $this->taskDomain
            ->setStatusWorking()
            ->setIdentifier(gethostname())
            ->setTsStarted(time())
            ->setStartedCount($this->taskDomain->getStartedCount() + 1);

        $this->logTaskStart();
        $this->logNewRelicStart();

        $task = $this->getTaskInstance();
        $task->setEncodedData($this->taskDomain->getData());
        if ($this->hasResourceContainer()) {
            $this->setResourceContainer($this->resourceContainer);
        }
        $this->setRequestUuid();
        try {
            $task->execute();
        } catch (\Exception $e) {
            $this->timerStop();
            $this->taskerExecution->setException($e);
            $this->taskDomain->setStatusBroken($this->getTotalTime());
            $this->logTaskExecution();
            $this->logNewRelicFailed($e);
            throw $e;
        }

        $this->timerStop();
        $this->taskDomain->setStatusDone($this->getTotalTime());
        $this->logTaskExecution();
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
        $this->logTaskExecution();
        return $this;
    }

    private function logTaskExecution()
    {
        $this->logger !== null && $this->logger->log($this->taskerExecution);
        return $this;
    }

    private function logNewRelicStart()
    {
        if ($this->newRelic !== null) {
            $this->newRelic->startTransaction(new StringLiteral($this->taskDomain->getTask()));
        }
        return $this;
    }

    private function logNewRelicEnd()
    {
        if ($this->newRelic !== null) {
            $this->newRelic->endTransaction();
        }
        return $this;
    }

    private function logNewRelicFailed(\Exception $exception)
    {
        if ($this->newRelic !== null) {
            $this->newRelic->failedTransaction($exception);
        }
        return $this;
    }

}