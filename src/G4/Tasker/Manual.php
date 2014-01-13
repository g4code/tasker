<?php
namespace G4\Tasker;

use G4\Tasker\Model\Mapper\Mysql\Recurring as RecurringMapper;
use G4\Tasker\Model\Mapper\Mysql\Task as TaskMapper;
use G4\Tasker\Model\Domain\Task as TaskDomain;

class Manual
{
    public function add($task, array $data = null, $priority = Consts::PRIORITY_MEDIUM)
    {
        if(!is_string($task)) {
            throw new \Exception('Task name must be string');
        }

        if(null !== $data && (!is_array($data) || empty($data)) ) {
            throw new \Exception('If data is set, it must be non empty array');
        }

        $priority = intval($priority);
        if(!$priority) {
            $priority = Consts::PRIORITY_MEDIUM;
        }

        $parsedData = null !== $data
            ? json_encode($data)
            : '';

        $taskMapper = new TaskMapper();

        $domain = new TaskDomain();
        $domain
            ->setRecurringId(0)
            ->setTask($task)
            ->setData($parsedData)
            ->setStatus(Consts::STATUS_PENDING)
            ->setPriority($priority)
            ->setCreatedTs(time())
            ->setExecTime(0)
            ->setMapper($taskMapper)
            ->save();
    }
}