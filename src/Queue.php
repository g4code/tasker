<?php
namespace G4\Tasker;

use G4\Tasker\Model\Domain\Task as TaskDomain;

class Queue
{

    private $dbAdapter;

    /**
     * @var Identifier
     */
    private $identifier;

    /**
     * @var array
     */
    private $tasks;


    public function __construct(\G4\DataMapper\Adapter\Mysql\Db $dbAdapter)
    {
        $this->dbAdapter = $dbAdapter;
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
        return new \G4\Tasker\Model\Mapper\Mysql\Task($this->dbAdapter);
    }
}