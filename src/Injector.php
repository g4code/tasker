<?php
namespace G4\Tasker;

use G4\Tasker\Model\Mapper\Mysql\Recurring as RecurringMapper;
use G4\Tasker\Model\Mapper\Mysql\Task as TaskMapper;
use G4\Tasker\Model\Domain\Task as TaskDomain;
use G4\Cron\CronExpression;

class Injector
{
    public function run()
    {
        $recuringMapper = new RecurringMapper();

        $this->_data = $recuringMapper->getNextTasks();

        if(empty($this->_data)) {
            return false;
        }

        $taskMapper = new TaskMapper();

        foreach ($this->_data as $item) {

            $expression = CronExpression::factory($item->getFrequency());
            $ts = strtotime($expression->getNextRunDate()->format('Y-m-d H:i:s'));

            $domain = new TaskDomain();
            $domain
                ->setRecurringId($item->getId())
                ->setTask($item->getTask())
                ->setData($item->getData())
                ->setStatus(Consts::STATUS_PENDING)
                ->setPriority($item->getPriority())
                ->setCreatedTs($ts)
                ->setExecTime(0);
            
            $taskMapper->insert($domain);
        }
    }
}