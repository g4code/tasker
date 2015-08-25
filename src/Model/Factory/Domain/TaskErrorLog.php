<?php

namespace G4\Tasker\Model\Factory\Domain;

use G4\DataMapper\Factory\Domain\DomainAbstract;

class TaskErrorLog extends DomainAbstract
{
    /**
     * @var \G4\Tasker\Model\Domain\TaskErrorLog
     */
    protected $_domainModel;

    protected $_domainModelName = '\G4\Tasker\Model\Domain\TaskErrorLog';

    public function _objectFactory()
    {
        $this->_domainModel
            ->setId($this->getDataProperty($this->_domainModel->getIdKey()))
            ->setTaskId($this->getDataProperty('task_id'))
            ->setIdentifier($this->getDataProperty('identifier'))
            ->setTask($this->getDataProperty('task'))
            ->setData($this->getDataProperty('data'))
            ->setTsStarted($this->getDataProperty('ts_started'))
            ->setDateStarted($this->getDataProperty('date_started'))
            ->setExecTime($this->getDataProperty('exec_time'))
            ->setLog($this->getDataProperty('log'));
    }

    /**
     * (non-PHPdoc)
     * @see \G4\DataMapper\Factory\Domain\DomainAbstract::createObject()
     * @return \G4\Tasker\Model\Domain\TaskErrorLog
     */
    public function createObject($data = null)
    {
        return parent::createObject($data);
    }

}