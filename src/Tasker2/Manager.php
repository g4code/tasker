<?php

namespace G4\Tasker\Tasker2;

use G4\Tasker\Consts;
use Model\Domain\RabbitMq\RabbitMqConsts;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

class Manager
{
    const LIMIT_DEFAULT = 100;

    /**
     * @var array | \G4\Tasker\Model\Domain\Task[]
     */
    private $tasks;

    /**
     * @var AMQPStreamConnection
     */
    private $rabbitMqConnection;

    private $limit;

    /**
     * @var \G4\Tasker\Model\Repository\TaskRepositoryInterface
     */
    private $taskRepository;

    /**
     * @var MessageOptions
     */
    private $messageOptions;

    public function __construct(
        \G4\Tasker\Model\Repository\TaskRepositoryInterface $taskRepository,
        AMQPStreamConnection $rabbitMqConnection,
        MessageOptions $messageOptions
    ) {
        $this->taskRepository = $taskRepository;
        $this->limit = self::LIMIT_DEFAULT;
        $this->rabbitMqConnection = $rabbitMqConnection;
        $this->messageOptions = $messageOptions;
    }

    public function run()
    {
        $this
            ->getReservedTasks()
            ->addToMessageQueue()
            ->updateStatus();
    }

    private function getReservedTasks()
    {
        $this->tasks = $this->taskRepository->findReserved($this->limit);
        return $this;
    }

    private function getMessages()
    {
        if (count($this->tasks) === 0) {
            throw new \RuntimeException('No tasks available');
        }

        // todo extract to Factory
        return array_map(function (\G4\Tasker\Model\Domain\Task $task) {
            return (new AmqpMessageFactory($task, $this->messageOptions->getDeliveryMode()))->create();
        }, $this->tasks);
    }

    private function addToMessageQueue()
    {
        $channel = $this->rabbitMqConnection->channel();

        try {
            $messages = $this->getMessages();
            foreach ($messages as $message) {
                $decodedMessageBody = json_decode($message->getBody(), 1);
                $binding = ($this->messageOptions->hasBindingHP() && isset($decodedMessageBody[Consts::PARAM_PRIORITY])
                    && ($decodedMessageBody[Consts::PARAM_PRIORITY] > Consts::PRIORITY_50))
                    ? $this->messageOptions->getBindingHP()
                    : $this->messageOptions->getBinding();

                $channel->batch_basic_publish(
                    $message,
                    $this->messageOptions->getExchange(),
                    $binding
                );

            }
            $channel->publish_batch();
        } catch (\Exception $e) {
            // todo throw exception if unable to add messages to queue
        } finally {
            $channel->close();
        }

        return $this;
    }

    private function updateStatus()
    {
        if (count($this->tasks) === 0) {
            return $this;
        }
        // update status sent to queue for execution
        $this->taskRepository->updateStatus(10, ...$this->tasks);
        return $this;
    }
}