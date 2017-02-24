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
     * @var \G4\Tasker\Model\Repository\RecurringRepositoryInterface
     */
    private $recurringRepository;

    /**
     * @var \G4\Tasker\Model\Repository\TaskRepositoryInterface
     */
    private $taskRepository;


    public function __construct(\G4\Tasker\Model\Repository\TaskRepositoryInterface $taskRepository, \G4\Tasker\Model\Repository\RecurringRepositoryInterface $recurringRepository)
    {
        $this->taskRepository  = $taskRepository;
        $this->recurringRepository = $recurringRepository;
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
        return gethostname() === $this->identifier->getPrimary();
    }

    private function fetchRecurringTasks()
    {
        $this->data = $this->recurringRepository->getNextTasks();
    }

    private function hasData()
    {
        return !empty($this->data);
    }

    private function saveOneTask($item)
    {
        $expression = CronExpression::factory($item->getFrequency());
        $ts = strtotime($expression->getNextRunDate()->format('Y-m-d H:i:s'));

        $domain = new TaskDomain(
            $this->identifier->getOne(),
            $item->getTask(),
            $item->getData(),
            $item->getPriority(),
            $ts
        );

        $domain
            ->setRecurringId($item->getRecuId());

        $this->taskRepository->add($domain);
    }

    private function saveTasks()
    {
        foreach ($this->data as $item) {
            $this->saveOneTask($item);
        }
    }
}