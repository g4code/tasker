<?php

namespace G4\Tasker\Tasker2;

class AmqpMessageFactory
{
    /**
     * @var \G4\Tasker\Model\Domain\Task
     */
    private $task;

    /**
     * @var int
     */
    private $deliveryMode;

    public function __construct(\G4\Tasker\Model\Domain\Task $task, $deliveryMode = 2)
    {
        $this->task = $task;
        $this->deliveryMode = $deliveryMode;
    }

    /**
     * return AMQPMessage
     */
    public function create()
    {
        return new \PhpAmqpLib\Message\AMQPMessage(
            json_encode($this->task->getRawData()),
            [MessageOptions::DELIVERY_MODE => $this->deliveryMode]
        );
    }
}