<?php

namespace G4\Tasker\Tasker2;

use G4\Tasker\TaskAbstract;
use G4\Tasker\Tasker2\Exception\TasksRelocatedToPersistenceException;
use G4\Tasker\Tasker2\Profiler\RabbitMq\Ticker as RabbitMqTicker;
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
    ) {
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
        $this->profile('tasker_delayed_tasks_persistence', function () {
            $this->queue->save();
        }, $tasks);
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

        try {
            $channel = $this->profile('tasker_channel_open', function () {
                return $this->AMQPConnection->channel();
            });

            $messages = array_map(function (TaskAbstract $taskAbstract) {
                $task = (new TaskFactory($taskAbstract))->create();
                $task->setRequestUuid($this->getRequestUuid());
                return (new AmqpMessageFactory($task, $this->messageOptions->getDeliveryMode()))->create();
            }, $tasks);

            $queuePublisher = new BatchPublisher($channel, $this->messageOptions);
            $this->profile('tasker_publish_batch', function () use ($queuePublisher, $messages) {
                $queuePublisher->publish(...$messages);
            }, $tasks);
        } catch (\Exception $e) {
            $this->errorLogger !== null && $this->errorLogger->log($e);
            $this->delayCurrentTasks($tasks);
            return $this;
        }

        // moved from above try catch block to avoid duplicating tasks in case of RabbitMQ connection close exception
        try {
            $this->profile('tasker_channel_close', function () use ($channel) {
                $channel->close();
            });
        } catch (\Exception $e) {
            $this->errorLogger !== null && $this->errorLogger->log($e);
        }
        return $this;
    }

    private function delayCurrentTasks($tasks)
    {
        $tasksModified = array_map(function (TaskAbstract $task) {
            return $task->relocateToPersistence();
        }, $tasks);

        foreach ($tasksModified as $task) {
            $this->queue->add($task);
        }
        $this->profile('tasker_fallback_db_persistence', function () {
            $this->queue->save();
        }, $tasksModified);
        $this->errorLogger !== null
            && $this->errorLogger->log(new TasksRelocatedToPersistenceException(count($tasksModified))
        );
    }

    /**
     * @param string $operation
     * @param callable $callback
     * @param TaskAbstract[] $tasks
     * @return mixed
     */
    private function profile($operation, callable $callback, array $tasks = [])
    {
        $ticker = RabbitMqTicker::getInstance();
        $tickerId = $ticker->start();
        $ticker->setOperation($tickerId, $operation);

        if (count($tasks) > 0) {
            $ticker->setTasks($tickerId, $this->getTaskDetails($tasks));
        }

        try {
            return $callback();
        } finally {
            $ticker->end($tickerId);
        }
    }

    /**
     * @param TaskAbstract[] $tasks
     * @return array
     */
    private function getTaskDetails(array $tasks)
    {
        return array_map(function (TaskAbstract $task) {
            return [
                'task_name'  => $task->getName(),
                'parameters' => $task->getData(),
            ];
        }, $tasks);
    }

    private function getRequestUuid()
    {
        return $this->requestUuid ?? (string)Uuid::generate();
    }
}
