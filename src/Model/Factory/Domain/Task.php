<?php
namespace G4\Tasker\Model\Factory\Domain;

use G4\DataMapper\Factory\Domain\DomainAbstract;

class Task extends DomainAbstract
{
    /**
     * @var \G4\Tasker\Model\Domain\Task
     */
    protected $_domainModel;

    protected $_domainModelName = '\G4\Tasker\Model\Domain\Task';

    protected function _objectFactory()
    {
        $this->_domainModel
            ->setId($this->getDataProperty($this->_domainModel->getIdKey()))
            ->setRecurringId($this->getDataProperty('recu_id'))
            ->setTask($this->getDataProperty('task'))
            ->setData($this->getDataProperty('data'))
            ->setIdentifier($this->getDataProperty('identifier'))
            ->setStatus($this->getDataProperty('status'))
            ->setPriority($this->getDataProperty('priority'))
            ->setCreatedTs($this->getDataProperty('created_ts'))
            ->setStartedTime($this->getDataProperty('started_time'))
            ->setExecTime($this->getDataProperty('exec_time'))
            ->setStartedCount($this->getDataProperty('started_count'));
    }

    /**
     * (non-PHPdoc)
     * @see \G4\DataMapper\Factory\Domain\DomainAbstract::createObject()
     * @return \G4\Tasker\Model\Domain\Task
     */
    public function createObject($data = null)
    {
        return parent::createObject($data);
    }
}