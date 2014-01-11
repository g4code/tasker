<?php
namespace G4\Tasker;

use G4\Tasker\Model\Mapper\Mysql\Task as TaskMapper;

class Manager
{
    const TIME_FORMAT = 'Y-m-d H:i:s';

    private $_benchmarkStart;

    private $_benchmarkStop;

    private $_tasks;

    private $_options;

    private $_runner;

    private $_limit = Consts::LIMIT_DEFAULT;

    public function __construct()
    {
        $this->_benchmarkStart();
    }

    public function run()
    {
        $this
            ->_getTasks($this->_limit)
            ->_runTasks();
    }

    private function _getTasks($limit)
    {
        $limit = intval($limit);
        if(!$limit) {
            $limit = Consts::LIMIT_DEFAULT;
        }

        $mapper = new TaskMapper();

        $identity = $mapper->getIdentity();

        $identity
            ->field('status')
            ->eq(Consts::STATUS_PENDING)
            ->field('created_ts')
            ->le( time() )
            ->setOrderBy('priority', 'DESC')
            ->setLimit( $limit );

        $this->_tasks = $mapper->findAll($identity);
        return $this;
    }

    private function _runTasks()
    {
        if($this->_tasks->count() > 0) {

            $forker = new Forker();
            $forker
                ->setOptions($this->getOptions())
                ->setRunner($this->getRunner());

            foreach ($this->_tasks as $task) {
                $forker->run($task);
            }
        }

        $this
            ->_benchmarkStop()
            ->_writeLog();
    }

    private function _benchmarkStart()
    {
        $this->_benchmarkStart = microtime(true);
        return $this;
    }

    private function _benchmarkStop()
    {
        $this->_benchmarkStop = microtime(true);
        return $this;
    }

    private function _writeLog()
    {
        echo "Started: " . date(self::TIME_FORMAT, $this->_benchmarkStart) . "\n";
        echo "Execution time: " . ($this->_benchmarkStop - $this->_benchmarkStart) . "\n";
    }

    public function getRunner()
    {
        return $this->_runner;
    }

    public function setRunner($value)
    {
        $this->_runner = $value;
        return $this;
    }

    public function getOptions()
    {
        return $this->_options;
    }

    public function setOptions(array $value)
    {
        $this->_options = $value;
        return $this;
    }

    public function addOption($value)
    {
        $this->_options[] = $value;
        return $this;
    }

    public function getLimit()
    {
        return $this->_limit;
    }

    public function setLimit($value)
    {
        $this->_limit = $value;
        return $this;
    }
}
