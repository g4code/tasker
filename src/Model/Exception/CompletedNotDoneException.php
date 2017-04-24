<?php

namespace G4\Tasker\Model\Exception;


class CompletedNotDoneException extends \Exception
{
    public function __construct($message = '', $code = 0, \Exception $previous = null)
    {
        if (!$message) {
            $message = 'COMPLETED_NOT_DONE';
        }

        parent::__construct($message, $code, $previous);
    }
}