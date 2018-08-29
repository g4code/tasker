<?php
namespace G4\Tasker;

use G4\Tasker\Model\Domain\Task as TaskDomain;
use G4\Tasker\Model\Repository\Mysql\TaskRepository;

class Queue
{
    /**
     * @var TaskRepository
     */
    private $taskRepository;

    /**
     * @var Identifier
     */
    private $identifier;

    /**
     * @var string
     */
    private $requestUuid;

    /**
     * @var array
     */
    private $tasks;


    public function __construct(TaskRepository $taskRepository)
    {
        $this->taskRepository = $taskRepository;
        $this->tasks = [];
    }

    public function add(\G4\Tasker\TaskAbstract $task)
    {
        $domain = new TaskDomain(
            $this->identifier->getOne(),
            $task->getName(),
            $task->getEncodedData(),
            $task->getPriority(),
            $task->getTsCreated()
        );
        $domain
            ->setRequestUuid($this->requestUuid);
        $this->tasks[] = $domain;
        return $this;
    }

    /**
     * @param string|array $hostname
     * @return Queue
     */
    public function setHostname($hostname)
    {
        $this->identifier = new Identifier($hostname);
        return $this;
    }

    /**
     * @param string $requestUuid
     * @return Queue
     */
    public function setRequestUuid($requestUuid)
    {
        $this->requestUuid = $requestUuid;
        return $this;
    }

    public function save()
    {
        $this->taskRepository->addBulk($this->tasks);
        return  $this;
    }

}