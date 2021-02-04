<?php

namespace G4\Tasker\Tasker2\Queue;

use G4\Tasker\Tasker2\MessageOptions;
use G4\ValueObject\Dictionary;
use G4\ValueObject\StringLiteral;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Message\AMQPMessage;

class BatchPublisher
{
    /**
     * @var MessageOptions
     */
    private $messageOptions;
    /**
     * @var AMQPChannel
     */
    private $channel;

    public function __construct(AMQPChannel $channel, MessageOptions $messageOptions)
    {
        $this->channel = $channel;
        $this->messageOptions = $messageOptions;
    }

    public function publish(AMQPMessage ...$messages)
    {
        foreach ($messages as $message) {
            $decodedMessageBody = json_decode($message->getBody(), true);

            $task = isset($decodedMessageBody['task']) ? new StringLiteral($decodedMessageBody['task']) : null;

            $binding = (new BindingResolver($this->messageOptions))->resolve(new Dictionary($decodedMessageBody), $task);

            $this->channel->batch_basic_publish(
                $message,
                $this->messageOptions->getExchange(),
                $binding
            );
        }
        $this->channel->publish_batch();
    }
}