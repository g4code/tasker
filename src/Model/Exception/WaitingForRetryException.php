<?php

namespace G4\Tasker\Model\Exception;


class WaitingForRetryException extends \Exception
{
    public function __construct($message = '', $code = 0, \Exception $previous = null)
    {
        if (!$message) {
            $message = 'WAITING_FOR_RETRY';
        }

        parent::__construct($message, $code, $previous);
    }
}