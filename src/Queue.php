<?php
namespace G4\Tasker;

use G4\Tasker\Model\Domain\Task as TaskDomain;

class Queue
{
    private $tasks;

    public function __construct()
    {
        $this->tasks = array();
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
            ->setExecTime(0);

        $this->tasks[] = $domain;
        return  $this;
    }

    public function save()
    {
        $mapper = $this->_getMapperInstance();
        if (count($this->tasks) > 0) {
            $mapper->insertBulk($this->tasks);
        }
        return  $this;
    }

    private function _getMapperInstance()
    {
        return new \G4\Tasker\Model\Mapper\Mysql\Task();
    }
}