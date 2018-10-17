<?php

namespace G4\Tasker\Tasker2;

use G4\Tasker\TaskAbstract;
use Model\Domain\RabbitMq\RabbitMqConsts;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

class TaskQueue
{
    /**
     * @var \G4\Tasker\Queue
     */
    private $queue;

    /**
     * @var AMQPStreamConnection
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

    public function __construct(
        \G4\Tasker\Queue $queue,
        AMQPStreamConnection $AMQPConnection,
        MessageOptions $messageOptions
    )
    {
        $this->queue = $queue;
        $this->AMQPConnection = $AMQPConnection;
        $this->messageOptions = $messageOptions;
        $this->tasks = [];
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
        $channel = $this->AMQPConnection->channel();

        $messages = array_map(function (TaskAbstract $taskAbstract) {
            $task = (new TaskFactory($taskAbstract))->create();
            return (new AmqpMessageFactory($task, $this->messageOptions->getDeliveryMode()))->create();
        }, $tasks);

        foreach ($messages as $message) {
            $channel->batch_basic_publish(
                $message,
                $this->messageOptions->getExchange(),
                $this->messageOptions->getBinding()
            );
        }
        $channel->publish_batch();
        $channel->close();
    }

}
