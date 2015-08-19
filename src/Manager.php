<?php
namespace G4\Tasker;

use G4\Tasker\Model\Mapper\Mysql\Task as TaskMapper;

class Manager extends TimerAbstract
{
    const MAX_RETRY_ATTEMPTS        = 3;
    const TIME_FORMAT               = 'Y-m-d H:i:s';
    const RESETtasks_AFTER_SECONDS = 60;

    private $delay;

    private $limit;

    private $maxNoOfPhpProcesses;

    private $numberOfGroupedTasks;

    /**
     * @var array
     */
    private $options;

    private $runner;

    private $tasks;

    /**
     *
     * @var \G4\Tasker\Model\Mapper\Mysql\Task
     */
    private $taskMapper;

    public function __construct()
    {
        $this->timerStart();

        $this->taskMapper = new TaskMapper();

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
        if ($this->maxNoOfPhpProcesses == null) {
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
        $oldMultiRunnerTasks = $this->taskMapper->getOldMultiWorkingTasks();

        foreach ($oldMultiRunnerTasks as $task) {
            $task->setStatus(Consts::STATUS_PENDING);
            $this->taskMapper->insertOnDuplicateKeyUpdate($task);
        }

        return $this;
    }

    private function getReservedTasks()
    {
        $this->tasks = $this->taskMapper->getReservedTasks($this->limit);
        return $this;
    }

    private function forkProcesses()
    {
        $forker = new Forker();
        $forker->setRunner($this->getRunner());

        foreach ($this->tasks as $task) {

            try {

                usleep($this->delay != null ? $this->_delay : 0);

                if ($task instanceof \G4\Tasker\Model\Domain\Task) {
                    $this->addOption('id', $task->getId());
                } else {
                    $key = 'task_id';
                    $taskIds = array_map(function($item) use ($key) {
                        return $item[$key];
                    }, $task);
                    $this->addOption('ids', json_encode($taskIds));
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
        if($this->tasks->count() > 0) {

            \G4\DataMapper\Db\Db::getAdapter()->closeConnection();

            if (!is_null($this->numberOfGroupedTasks) && $this->numberOfGroupedTasks > 1) {
                $this->tasks = array_chunk($this->tasks->getRawData(), $this->numberOfGroupedTasks);
            }

            $this->forkProcesses();
        }

        $this
            ->timerStop()
            ->writeLog();
    }

    private function writeLog()
    {
        echo "Started: " . date(self::TIME_FORMAT, $this->getTimerStart()) . "\n";
        echo "Execution time: " . ($this->getTotalTime()) . "\n";
    }
}
