<?php

namespace G4\Tasker\Tasker2\Exception;

class RabbitmqNotAvailableException extends \RuntimeException
{
    public function __construct($message = null)
    {
        if (!$message) {
            $message = 'RabbitMQ connection is not available for Tasker Manager';
        }
        parent::__construct($message);
    }
}
