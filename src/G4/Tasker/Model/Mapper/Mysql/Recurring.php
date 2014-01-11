<?php

namespace G4\Tasker\Model\Mapper\Mysql;

use Gee\Log\Writer;

use G3\Model\Mapper\Mysql\MysqlAbstract;

class Recurring extends MysqlAbstract
{
    protected $_factoryDomainName = '\G4\Tasker\Model\Factory\Domain\Recurring';

    protected $_tableName = 'tasks_recurrings';

    public function getNextTasks()
    {
        $subSelect = $this->_db->select();

        $subSelect
            ->from('crons_tasks', array('recu_id'))
            ->distinct(true)
            ->where('status = ?', \G4\Tasker\Consts::STATUS_PENDING);

        $select = $this->_db->select();

        $select
            ->from($this->_tableName)
            ->where('recu_id NOT IN (?)', $subSelect);

        $this->_rawData = $this->_db->fetchAll($select);

        if(empty($this->_rawData)) {
            return false;
        }

        return $this->_returnCollection();
    }

}