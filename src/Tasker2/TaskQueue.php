<?php

namespace G4\Tasker\Tasker2;

use G4\Tasker\TaskAbstract;
use G4\Tasker\Tasker2\Exception\TasksRelocatedToPersistenceException;
use G4\Tasker\Tasker2\Queue\BatchPublisher;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use G4\ValueObject\Uuid;

class TaskQueue
{
    /**
     * @var \G4\Tasker\Queue
     */
    private $queue;

    /**
     * @var AMQPStreamConnection | null
     */
    private $AMQPConnection;

    /**
     * @var \G4\Tasker\TaskAbstract[] | array
     */
    private $tasks;

    /**
     * @var MessageOptions
     */
    private $messageOptions;

    /**
     * @var string
     */
    private $requestUuid;

    /**
     * @var \G4\Log\ErrorLogger
     */
    private $errorLogger;

    public function __construct(
        \G4\Tasker\Queue $queue,
        AMQPStreamConnection $AMQPConnection = null,
        MessageOptions $messageOptions,
        $requestUuid = null
    )
    {
        $this->queue = $queue;
        $this->AMQPConnection = $AMQPConnection;
        $this->messageOptions = $messageOptions;
        $this->tasks = [];
        $this->requestUuid = $requestUuid;
    }

    public function setErrorLogger(\G4\Log\ErrorLogger $logger)
    {
        $this->errorLogger = $logger;
        return $this;
    }

    public function add(\G4\Tasker\TaskAbstract $task)
    {
        $this->tasks[] = clone $task;
        return $this;
    }

    public function save()
    {
        if (count($this->tasks) === 0) {
            return $this;
        }

        $currentTasks = [];
        $delayedTasks = [];

        foreach ($this->tasks as $task) {
            if ($task->delayed()) {
                $delayedTasks[] = $task;
            } else {
                $currentTasks[] = $task;
            }
        }

        $this->saveCurrentTasks($currentTasks);
        $this->saveDelayedTasks($delayedTasks);

        $this->tasks = [];
        return $this;
    }

    private function saveDelayedTasks($tasks)
    {
        if (count($tasks) === 0) {
            return $this;
        }

        foreach ($tasks as $task) {
            $this->queue->add($task);
        }
        $this->queue->save();
        return $this;
    }

    private function saveCurrentTasks($tasks)
    {
        if (count($tasks) === 0) {
            return $this;
        }

        if ($this->AMQPConnection === null) {
            // in case that rabbitmq is not available save tasks to database
            $this->delayCurrentTasks($tasks);
            return $this;
        }

        $channel = $this->AMQPConnection->channel();

        $messages = array_map(function (TaskAbstract $taskAbstract) {
            $task = (new TaskFactory($taskAbstract))->create();
            $task->setRequestUuid($this->getRequestUuid());
            return (new AmqpMessageFactory($task, $this->messageOptions->getDeliveryMode()))->create();
        }, $tasks);

        $queuePublisher = new BatchPublisher($channel, $this->messageOptions);
        $queuePublisher->publish(...$messages);
        $channel->close();
        return $this;
    }

    private function delayCurrentTasks($tasks)
    {
        $tasksModified = array_map(function(TaskAbstract $task) {
            return $task->relocateToPersistence();
        }, $tasks);

        $this->saveDelayedTasks($tasksModified);
        $this->errorLogger !== null
            && $this->errorLogger->log(new TasksRelocatedToPersistenceException(count($tasksModified))
        );
    }

    private function getRequestUuid()
    {
        return $this->requestUuid !== null
            ? $this->requestUuid
            : (string) Uuid::generate();
    }
}
