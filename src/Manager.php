<?php
namespace G4\Tasker;

use G4\Tasker\Model\Domain\Task;

class Manager extends TimerAbstract
{
    const MAX_RETRY_ATTEMPTS        = 3;
    const TIME_FORMAT               = 'Y-m-d H:i:s';
    const RESETtasks_AFTER_SECONDS  = 60;

    private $delay;

    private $limit;

    private $maxNoOfPhpProcesses;

    private $numberOfGroupedTasks;

    /**
     * @var array
     */
    private $options;

    private $runner;

    /**
     * @var array|\G4\Tasker\Model\Domain\Task[]
     */
    private $tasks;

    /**
     *
     * @var \G4\Tasker\Model\Repository\TaskRepositoryInterface
     */
    private $taskRepository;

    public function __construct(\G4\Tasker\Model\Repository\TaskRepositoryInterface $taskRepository)
    {
        $this->timerStart();

        $this->taskRepository = $taskRepository;

        $this->limit = Consts::LIMIT_DEFAULT;
    }

    public function addOption($key, $value)
    {
        $this->options[$key] = $value;
        return $this;
    }

    public function getLimit()
    {
        return $this->limit;
    }

    public function getOptions()
    {
        return $this->options;
    }

    public function getRunner()
    {
        return $this->runner;
    }

    public function run()
    {
        $this
            ->checkPhpProcessesCount()
            ->updateOldMultiRunnerTasks()
            ->getReservedTasks()
            ->runTasks();
    }

    public function setDelay($value)
    {
        $this->delay = $value;
        return $this;
    }

    public function setLimit($value)
    {
        $this->limit = $value;
        return $this;
    }

    public function setMaxNoOfPhpProcesses($value)
    {
        $this->maxNoOfPhpProcesses = $value;
        return $this;
    }

    public function setNumberOfGroupedTasks($value)
    {
        $this->numberOfGroupedTasks = $value;
        return $this;
    }

    public function setOptions(array $value)
    {
        $this->options = $value;
        return $this;
    }

    public function setRunner($value)
    {
        $this->runner = $value;
        return $this;
    }

    private function checkPhpProcessesCount()
    {
        if ($this->maxNoOfPhpProcesses === null) {
            return $this;
        }

        exec('ps -ef | grep -v grep | grep php | wc -l', $count);

        if ($count[0] >= $this->maxNoOfPhpProcesses) {
            throw new \Exception('Max number of active php processes reached.');
        }

        return $this;
    }

    private function updateOldMultiRunnerTasks()
    {
        /** @var \G4\Tasker\Model\Domain\Task[] $oldMultiRunnerTasks */
        $oldMultiRunnerTasks = $this->taskRepository->getOldMultiWorkingTasks();

        foreach ($oldMultiRunnerTasks as $task) {
            $task->setStatusPending();
            $this->taskRepository->update($task);
        }

        return $this;
    }

    private function getReservedTasks()
    {
        $this->tasks = $this->taskRepository->getReservedTasks($this->limit);
        return $this;
    }

    private function forkProcesses()
    {
        $forker = new Forker();
        $forker->setRunner($this->getRunner());

        foreach ($this->tasks as $task) {

            try {

                usleep($this->delay?: 0);

                if ($task instanceof \G4\Tasker\Model\Domain\Task) {
                    $this->addOption('id', $task->getId());
                } else {
                    $taskIds = array_map(function($task) {
                        /** @var Task $task */
                        return $task->getTaskId();
                    }, $task);
                    $this->addOption('ids', implode(',', $taskIds));
                }

                $forker
                    ->setOptions($this->getOptions())
                    ->fork();

            } catch (\Exception $e) {
                // log message here
                continue;
            }
        }
    }

    private function runTasks()
    {
        if(count($this->tasks) > 0) {

            // todo call pdo close
//            $this->taskRepository->closeConnection();

            if ($this->numberOfGroupedTasks !== null && $this->numberOfGroupedTasks > 1) {
                $this->tasks = array_chunk($this->tasks, $this->numberOfGroupedTasks);
            }

            $this->forkProcesses();
        }

        $this
            ->timerStop()
            ->writeLog();
    }

    private function writeLog()
    {
        echo 'Started: ' . date(self::TIME_FORMAT, $this->getTimerStart()) . "\n";
        echo 'Execution time: ' . $this->getTotalTime() . "\n";
    }
}
