<?php

namespace G4\Tasker\Model\Mapper\Mysql;

use G4\DataMapper\Mapper\Mysql\MysqlAbstract;

class TaskErrorLog extends MysqlAbstract
{
    protected $_factoryDomainName = '\G4\Tasker\Model\Factory\Domain\TaskErrorLog';

    protected $_tableName         = 'tasks_error_log';

}