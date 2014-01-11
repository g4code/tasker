<?php
namespace G4\Tasker;

use Gee\Log\Writer;

use G4\Tasker\Model\Mapper\Mysql\Recurring as RecurringMapper;
use G4\Tasker\Model\Mapper\Mysql\Task as TaskMapper;
use G4\Tasker\Model\Domain\Task as TaskDomain;

class Injector
{

    public function run()
    {
        $mapper = new RecurringMapper();

        $this->_data = $mapper->getNextTasks();

        if(empty($this->_data)) {
            Writer::preVarDump('no more recurring task to inject');
            return false;
        }

        $taskMapper = new TaskMapper();

        foreach ($this->_data as $item) {

            $expression = \Cron\CronExpression::factory($item->getFrequency());
            $ts = strtotime($expression->getNextRunDate()->format('Y-m-d H:i:s'));

            $domain = new TaskDomain();
            $domain
                ->setRecurringId($item->getId())
                ->setName($item->getTask())
                ->setData($item->getData())
                ->setStatus(Consts::STATUS_PENDING)
                ->setPriority(Consts::PRIORITY_LOW)
                ->setCreatedTs($ts)
                ->setExecTime(0)
                ->setMapper($taskMapper)
                ->save();
        }
    }
}