<?php

namespace G4\Tasker\Tasker2\Exception;

class TasksRelocatedToPersistenceException extends \RuntimeException
{
    public function __construct($countTasks)
    {
        $message = sprintf(
            'RabbitMQ connection is not available for Tasker TaskQueue, relocated %d tasks to persistence',
            $countTasks
        );

        parent::__construct($message);
    }
}
