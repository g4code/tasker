<?php

namespace G4\Tasker\Tasker2;

use G4\Tasker\Consts;
use G4\Tasker\Tasker2\Exception\RabbitmqNotAvailableException;
use G4\Tasker\Tasker2\Queue\BatchPublisher;
use PhpAmqpLib\Connection\AMQPStreamConnection;

class Manager
{
    const LIMIT_DEFAULT = 100;

    /**
     * @var array | \G4\Tasker\Model\Domain\Task[]
     */
    private $tasks;

    /**
     * @var AMQPStreamConnection | null
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

    /**
     * @var \G4\Log\ErrorLogger
     */
    private $errorLogger;

    public function __construct(
        \G4\Tasker\Model\Repository\TaskRepositoryInterface $taskRepository,
        AMQPStreamConnection $rabbitMqConnection = null,
        MessageOptions $messageOptions
    ) {
        $this->taskRepository = $taskRepository;
        $this->limit = self::LIMIT_DEFAULT;
        $this->rabbitMqConnection = $rabbitMqConnection;
        $this->messageOptions = $messageOptions;
    }

    public function setErrorLogger(\G4\Log\ErrorLogger $logger)
    {
        $this->errorLogger = $logger;
        return $this;
    }

    public function run()
    {
        if ($this->rabbitMqConnection === null) {
            // no rabbitmq connection is available
            $this->errorLogger !== null && $this->errorLogger->log(new RabbitmqNotAvailableException());
            return;
        }
        $this
            ->getReservedTasks()
            ->addToMessageQueue()
            ->updateStatus();
    }

    /**
     * Set how many tasks to fetch per run
     * @param $limit int
     */
    public function setFetchLimit($limit)
    {
        $this->limit = (int) $limit;
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
            $queuePublisher = new BatchPublisher($channel, $this->messageOptions);
            $queuePublisher->publish(...$messages);
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
        $this->taskRepository->updateStatus(Consts::STATUS_SENT_TO_QUEUE_FOR_EXECUTION, ...$this->tasks);
        return $this;
    }

    /**
     * @param int $olderThan
     * @param int $limit
     * @return void
     */
    public function deleteProcessedTasks($olderThan, $limit)
    {
        $this->taskRepository->deleteProcessedTasks($olderThan, $limit);
    }
}
