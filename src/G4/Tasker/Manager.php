<?php
namespace G4\Tasker;

use G4\Tasker\Model\Mapper\Mysql\Task as TaskMapper;

class Manager
{
    const TIME_FORMAT = 'Y-m-d H:i:s';

    private $_benchmarkStart;

    private $_benchmarkStop;

    private $_tasks;

    private $_env;

    private $_runner;

    /**
     *
     * @var int
     */
    private $_limit;

    public function __construct()
    {
        $this->_benchmarkStart();
    }

    public function run()
    {
        $this
            ->_getTasks()
            ->_runTasks();
    }

    private function _getTasks($limit = 100)
    {
        $mapper = new TaskMapper();

        $identity = $mapper->getIdentity();

        $identity
            ->field('status')
            ->eq(Consts::STATUS_PENDING)
            ->field('created_ts')
            ->le( time() )
            ->setLimit( $limit );

        $this->_tasks = $mapper->findAll($identity);
        return $this;
    }

    private function _runTasks()
    {
        if($this->_tasks->count() > 0) {

            $forker = new Forker();
            $forker
                ->setEnvironment($this->getEnvironment())
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
        echo "Cron started: " . date(self::TIME_FORMAT, $this->_benchmarkStart) . "\n";
        echo "Cron execution time: " . ($this->_benchmarkStop - $this->_benchmarkStart) . "\n";
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

    public function getEnvironment()
    {
        return $this->_env;
    }

    public function setEnvironment($value)
    {
        $this->_env = $value;
        return $this;
    }
}
