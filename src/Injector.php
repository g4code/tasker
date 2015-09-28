<?php
namespace G4\Tasker;

use G4\Tasker\Model\Mapper\Mysql\Recurring as RecurringMapper;
use G4\Tasker\Model\Mapper\Mysql\Task as TaskMapper;
use G4\Tasker\Model\Domain\Task as TaskDomain;
use G4\Cron\CronExpression;

class Injector
{

    /**
     * @var \G4\DataMapper\Collection\Content
     */
    private $data;

    /**
     * @var Identifier
     */
    private $identifier;

    /**
     * @var RecurringMapper
     */
    private $recurringMapper;

    /**
     * @var TaskMapper
     */
    private $taskMapper;


    public function __construct(\G4\Tasker\Model\Mapper\Mysql\Task $taskMapper, \G4\Tasker\Model\Mapper\Mysql\Recurring $recurringMapper)
    {
        $this->taskMapper      = $taskMapper;
        $this->recurringMapper = $recurringMapper;
    }

    public function run()
    {
        if (!$this->isPrimaryHost()) {
            return;
        }

        $this->fetchRecurringTasks();

        if($this->hasData()) {
            $this->saveTasks();
        }
    }

    /**
     * @param string|array $hostname
     * @return \G4\Tasker\Injector
     */
    public function setHostname($hostname)
    {
        $this->identifier = new Identifier($hostname);
        return $this;
    }

    public function isPrimaryHost()
    {
        return gethostname() == $this->identifier->getPrimary();
    }

    private function fetchRecurringTasks()
    {
        $this->data = $this->recurringMapper->getNextTasks();
    }

    private function hasData()
    {
        return !empty($this->data);
    }

    private function saveOneTask($item)
    {
        $expression = CronExpression::factory($item->getFrequency());
        $ts = strtotime($expression->getNextRunDate()->format('Y-m-d H:i:s'));

        $domain = new TaskDomain();
        $domain
            ->setRecurringId($item->getId())
            ->setTask($item->getTask())
            ->setData($item->getData())
            ->setIdentifier($this->identifier->getOne())
            ->setStatus(Consts::STATUS_PENDING)
            ->setPriority($item->getPriority())
            ->setTsCreated($ts)
            ->setTsStarted(0)
            ->setExecTime(0)
            ->setStartedCount(0);

        $this->taskMapper->insert($domain);
    }

    private function saveTasks()
    {
        foreach ($this->data as $item) {
            $this->saveOneTask($item);
        }
    }
}