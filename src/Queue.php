<?php
namespace G4\Tasker;

use G4\Tasker\Model\Domain\Task as TaskDomain;

class Queue
{

    /**
     * @var Identifier
     */
    private $identifier;

    /**
     * @var array
     */
    private $tasks;


    public function __construct()
    {
        $this->tasks = [];
    }

    public function add(\G4\Tasker\TaskAbstract $task)
    {
        $domain = new TaskDomain();

        $domain
            ->setRecurringId(0)
            ->setTask($task->getName())
            ->setData($task->getEncodedData())
            ->setStatus(Consts::STATUS_PENDING)
            ->setPriority($task->getPriority())
            ->setTsCreated($task->getTsCreated())
            ->setIdentifier($this->identifier->getOne())
            ->setExecTime(0);

        $this->tasks[] = $domain;
        return  $this;
    }

    /**
     * @param string|array $hostname
     * @return \G4\Tasker\Queue
     */
    public function setHostname($hostname)
    {
        $this->identifier = new Identifier($hostname);
        return $this;
    }

    public function save()
    {
        $mapper = $this->getMapperInstance();
        if (count($this->tasks) > 0) {
            $mapper->insertBulk($this->tasks);
        }
        return  $this;
    }

    private function getMapperInstance()
    {
        return new \G4\Tasker\Model\Mapper\Mysql\Task();
    }
}