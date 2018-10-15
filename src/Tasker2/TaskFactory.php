<?php

namespace G4\Tasker\Tasker2;


use G4\Tasker\Identifier;
use G4\Tasker\Model\Domain\Task;
use G4\Tasker\TaskAbstract;
use G4\ValueObject\Uuid;

class TaskFactory
{
    /**
     * @var TaskAbstract
     */
    private $taskAbstract;

    public function __construct(TaskAbstract $taskAbstract)
    {
        $this->taskAbstract = $taskAbstract;
    }

    public function create()
    {
        $task = new Task(
            new Identifier(['queue']),
            $this->taskAbstract->getName(),
            $this->taskAbstract->getEncodedData(),
            $this->taskAbstract->getPriority(),
            $this->taskAbstract->getTsCreated()
        );

        $requestUuid = isset($this->requestUuid)
            ? $this->requestUuid
            : (string) Uuid::generate();
        $task->setRequestUuid($requestUuid);

        return $task;
    }
}