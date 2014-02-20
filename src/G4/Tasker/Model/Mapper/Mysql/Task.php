<?php

namespace G4\Tasker\Model\Mapper\Mysql;

use G4\Tasker\Consts;
use G3\Model\Mapper\Mysql\MysqlAbstract;

class Task extends MysqlAbstract
{
    protected $_factoryDomainName = '\G4\Tasker\Model\Factory\Domain\Task';

    protected $_tableName = 'tasks';

    private $_identifier;

    public function reserveTasks($limit)
    {
        $limit = intval($limit);

        if(!$limit) {
            throw new \Exception('Limit is not valid');
        }

        $identity = $this->getIdentity()
            ->field('identifier')
            ->eq('')
            ->field('status')
            ->eq(Consts::STATUS_PENDING)
            ->field('created_ts')
            ->le(time())
            ->setOrderBy('priority', 'DESC')
            ->setLimit($limit);

        return $this->updateAll($identity, array('identifier' => $this->getIdentifier()));
    }

    public function getReservedTasks($limit)
    {
        $limit = intval($limit);

        if(!$limit) {
            throw new \Exception('Limit is not valid');
        }

        $identity = $this
            ->getIdentity()
            ->field('identifier')
            ->eq($this->getIdentifier())
            ->field('status')
            ->eq(Consts::STATUS_PENDING)
            ->setLimit($limit);

        return $this->findAll($identity);
    }

    public function getIdentifier()
    {
        if (!isset($this->_identifier)) {
            $this->_generateIdentifier();
        }
        return $this->_identifier;
    }

    private function _generateIdentifier()
    {
        $this->_identifier = gethostname()."|".time();
        return $this;
    }
}