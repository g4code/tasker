<?php
namespace G4\Tasker\Model\Factory\Domain;

use G3\Model\Factory\Domain\DomainAbstract;

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
            ->setName($this->getDataProperty('name'))
            ->setData($this->getDataProperty('data'))
            ->setStatus($this->getDataProperty('status'))
            ->setPriority($this->getDataProperty('priority'))
            ->setCreatedTs($this->getDataProperty('created_ts'))
            ->setExecTime($this->getDataProperty('exec_time'));
    }

    /**
     * (non-PHPdoc)
     * @see \G3\Model\Factory\Domain\DomainAbstract::createObject()
     * @return \G4\Tasker\Model\Domain\Task
     */
    public function createObject($data = null)
    {
        return parent::createObject($data);
    }
}