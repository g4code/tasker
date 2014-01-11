<?php

namespace G4\Tasker\Model\Mapper\Mysql;

use G3\Model\Mapper\Mysql\MysqlAbstract;

class Task extends MysqlAbstract
{
    protected $_factoryDomainName = '\G4\Tasker\Model\Factory\Domain\Task';

    protected $_tableName = 'tasks';
}