<?php

namespace G4\Tasker\Model\Mapper\Mysql;

use G4\Tasker\Consts;
use G4\DataMapper\Mapper\Mysql\MysqlAbstract;

class Task extends MysqlAbstract
{
    const MULTI_WORKING_OLDER_THAN = 600;   // 10 minutes
    const MULTI_WORKING_LIMIT = 20;         // how many tasks to reset to STATUS_PENDING

    protected $_factoryDomainName = '\G4\Tasker\Model\Factory\Domain\Task';

    protected $_tableName = 'tasks';

    private $_identifier;


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
            ->field('ts_created')
            ->le(time())
            ->field('started_count')
            ->eq(0)
            ->setLimit($limit);

        return $this->findAll($identity);
    }

    public function getOldMultiWorkingTasks()
    {
        $identity = $this
            ->getIdentity()
            ->field('status')
            ->eq(Consts::STATUS_MULTI_WORKING)
            ->field('ts_started')
            ->le(time() - self::MULTI_WORKING_OLDER_THAN)
            ->setLimit(self::MULTI_WORKING_LIMIT);

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
        $this->_identifier = gethostname();
        return $this;
    }
}