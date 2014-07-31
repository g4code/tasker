<?php
namespace G4\Tasker;

use G4\Tasker\Model\Mapper\Mysql\Task as TaskMapper;

class Manager extends TimerAbstract
{
    const MAX_RETRY_ATTEMPTS = 3;

    const TIME_FORMAT = 'Y-m-d H:i:s';

    const RESET_TASKS_AFTER_SECONDS = 60;

    private $_delay;

    private $_limit;

    private $_maxNoOfPhpProcesses;

    private $_options;

    private $_runner;

    private $_tasks;

    /**
     *
     * @var G4\Tasker\Model\Mapper\Mysql\Task
     */
    private $_taskMapper;

    public function __construct()
    {
        $this->timerStart();

        $this->_taskMapper = new TaskMapper();

        $this->_limit = Consts::LIMIT_DEFAULT;
    }

    public function addOption($key, $value)
    {
        $this->_options[$key] = $value;
        return $this;
    }

    public function getLimit()
    {
        return $this->_limit;
    }

    public function getOptions()
    {
        return $this->_options;
    }

    public function getRunner()
    {
        return $this->_runner;
    }

    public function run()
    {
        $this
            ->_checkPhpProcessesCount()
            ->_resetTasks();

        $this->_taskMapper->transactionBegin();

        try {
            $this
                ->_reserveTasks()
                ->_getReservedTasks();
        } catch (\Exception $e) {
            $this->_taskMapper->transactionRollback();
            return $this;
        }

        $this->_taskMapper->transactionCommit();

        $this->_runTasks();
    }

    public function setDelay($value)
    {
        $this->_delay = $value;
        return $this;
    }

    public function setLimit($value)
    {
        $this->_limit = $value;
        return $this;
    }

    public function setMaxNoOfPhpProcesses($value)
    {
        $this->_maxNoOfPhpProcesses = $value;
        return $this;
    }

    public function setOptions(array $value)
    {
        $this->_options = $value;
        return $this;
    }

    public function setRunner($value)
    {
        $this->_runner = $value;
        return $this;
    }

    private function _checkPhpProcessesCount()
    {
        if ($this->_maxNoOfPhpProcesses == null) {
            return $this;
        }

        exec('ps -ef | grep -v grep | grep php | wc -l', $count);

        if ($count[0] >= $this->_maxNoOfPhpProcesses) {
            throw new \Exception('Max number of active php processes reached.');
        }

        return $this;
    }

    private function _getReservedTasks()
    {
        $this->_tasks = $this->_taskMapper->getReservedTasks($this->_limit);
        return $this;
    }

    private function _reserveTasks()
    {
        $this->_taskMapper->reserveTasks($this->_limit);
        return $this;
    }

    private function _resetTasks()
    {
        $cleaner = new Cleaner();
        $cleaner
            ->setTimeDelay(self::RESET_TASKS_AFTER_SECONDS)
            ->setMaxRetryAttempts(self::MAX_RETRY_ATTEMPTS)
            ->run();
        return $this;
    }

    private function _runTasks()
    {
        if($this->_tasks->count() > 0) {

            \G4\DataMapper\Db\Db::getAdapter()->closeConnection();

            $forker = new Forker();
            $forker->setRunner($this->getRunner());

            foreach ($this->_tasks as $task) {

                try {

                    usleep($this->_delay != null ? $this->_delay : 0);

                    $this->addOption('id', $task->getId());

                    $forker
                        ->setOptions($this->getOptions())
                        ->fork();
                } catch (\Exception $e) {
                    // log message here
                    continue;
                }
            }
        }

        $this
            ->timerStop()
            ->_writeLog();
    }

    private function _writeLog()
    {
        echo "Started: " . date(self::TIME_FORMAT, $this->getTimerStart()) . "\n";
        echo "Execution time: " . ($this->getTotalTime()) . "\n";
    }
}
