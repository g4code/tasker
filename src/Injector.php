<?php
namespace G4\Tasker;

use G4\Tasker\Model\Domain\Recurring;
use G4\Tasker\Model\Domain\Task as TaskDomain;
use G4\Tasker\Model\Repository\RecurringRepositoryInterface;
use G4\Tasker\Model\Repository\TaskRepositoryInterface;
use G4\Cron\CronExpression;
use G4\ValueObject\Uuid;

class Injector
{

    /**
     * @var array|Recurring[]
     */
    private $data;

    /**
     * @var Identifier
     */
    private $identifier;

    /**
     * @var RecurringRepositoryInterface
     */
    private $recurringRepository;

    /**
     * @var TaskRepositoryInterface
     */
    private $taskRepository;

    /**
     * @param TaskRepositoryInterface $taskRepository
     * @param RecurringRepositoryInterface $recurringRepository
     */
    public function __construct(TaskRepositoryInterface $taskRepository, RecurringRepositoryInterface $recurringRepository)
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
     * @return Injector
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
        return count($this->data) > 0;
    }

    private function saveOneTask(Recurring $item)
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
            ->setRecurringId($item->getRecuId())
            ->setRequestUuid(Uuid::generate());

        $this->taskRepository->add($domain);
    }

    private function saveTasks()
    {
        foreach ($this->data as $item) {
            $this->saveOneTask($item);
        }
    }
}