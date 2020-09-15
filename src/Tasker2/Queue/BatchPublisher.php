<?php

namespace G4\Tasker\Tasker2\Queue;

use G4\Tasker\Consts;
use G4\Tasker\Tasker2\MessageOptions;
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
            $binding = ($this->messageOptions->hasBindingHP() && isset($decodedMessageBody[Consts::PARAM_PRIORITY])
                && ($decodedMessageBody[Consts::PARAM_PRIORITY] > Consts::PRIORITY_50))
                ? $this->messageOptions->getBindingHP()
                : $this->messageOptions->getBinding();
            $this->channel->batch_basic_publish(
                $message,
                $this->messageOptions->getExchange(),
                $binding
            );
        }
        $this->channel->publish_batch();
    }
}