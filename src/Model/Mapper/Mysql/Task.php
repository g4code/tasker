<?php

namespace G4\Tasker\Model\Mapper\Mysql;

use G4\Tasker\Consts;
use G4\DataMapper\Mapper\Mysql\MysqlAbstract;

class Task extends MysqlAbstract
{
    protected $_factoryDomainName = '\G4\Tasker\Model\Factory\Domain\Task';

    protected $_tableName = 'tasks';

    private $_identifier;

    private $_timeDelay;

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

    public function resetTaskStatusPendingWithIdentifier()
    {
        $identity = $this->getIdentity()
            ->field('identifier')
            ->neq('')
            ->field('status')
            ->eq(Consts::STATUS_PENDING)
            ->field('created_ts')
            ->le(time() - $this->_timeDelay);
        $this->updateAll($identity, array('identifier' => ''));
        return $this;
    }

    public function resetTaskStatusWorking()
    {
        $identity = $this->getIdentity()
            ->field('status')
            ->eq(Consts::STATUS_WORKING)
            ->field('started_time')
            ->le(time() - $this->_timeDelay);
        $this->updateAll($identity, array('identifier' => '', 'status' => Consts::STATUS_PENDING, 'started_time' => 0));
        return $this;
    }

    public function setRetryFailedStatus($maxRetryAttempts)
    {
        $identity = $this->getIdentity()
            ->field('started_count')
            ->ge($maxRetryAttempts)
            ->field('status')
            ->eq(Consts::STATUS_WORKING)
            ->field('started_time')
            ->le(time() - $this->_timeDelay);
        $this->updateAll($identity, array('status' => Consts::STATUS_RETRY_FAILED));
        return $this;
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

    public function setTimeDelay($value)
    {
        $this->_timeDelay = $value;
        return $this;
    }

    private function _generateIdentifier()
    {
        $this->_identifier = gethostname()."|".time();
        return $this;
    }
}