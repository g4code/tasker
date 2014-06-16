<?php
namespace G4\Tasker;

use G4\Tasker\Model\Domain\Task as TaskDomain;

class Queue
{
    private $_tasks;

    public function __construct()
    {
        $this->_tasks = array();
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

        $this->_tasks[] = $domain;
        return  $this;
    }

    public function save()
    {
        $mapper = $this->_getMapperInstance();
        $mapper->insertBulk($this->_tasks);
        return  $this;
    }

    private function _getMapperInstance()
    {
        return new \G4\Tasker\Model\Mapper\Mysql\Task();
    }
}