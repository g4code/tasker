<?php
namespace G4\Tasker\Model\Factory\Domain;

use G4\DataMapper\Factory\Domain\DomainAbstract;

class Recurring extends DomainAbstract
{
    /**
     * @var \G4\Tasker\Model\Domain\Recurring
     */
    protected $_domainModel;

    protected $_domainModelName = '\G4\Tasker\Model\Domain\Recurring';

    protected function _objectFactory()
    {
        $this->_domainModel
            ->setId($this->getDataProperty($this->_domainModel->getIdKey()))
            ->setTask($this->getDataProperty('task'))
            ->setFrequency($this->getDataProperty('frequency'))
            ->setData($this->getDataProperty('data'))
            ->setStatus($this->getDataProperty('status'))
            ->setPriority($this->getDataProperty('priority'));
    }

    /**
     * (non-PHPdoc)
     * @see \G4\DataMapper\Factory\Domain\DomainAbstract::createObject()
     * @return \G4\Tasker\Model\Domain\Recurring
     */
    public function createObject($data = null)
    {
        return parent::createObject($data);
    }
}