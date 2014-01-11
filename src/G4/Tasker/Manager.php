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
            $forker->setRunner($this->getRunner());

            $mapper = new TaskMapper;

            foreach ($this->_tasks as $task) {
                $task->addMapper($mapper);

                // begin transaction
                $mapper->transactionBegin();

                // mark task as working
                $task->setStatus(Consts::STATUS_WORKING);
                $task->save();

                $this->addOption(array('id' => $task->getId()));

                try {
                    $forker
                        ->setOptions($this->getOptions())
                        ->fork();
                } catch (\Exception $e) {
                    // rollback
                    $mapper->transactionRollback();
                    // log message here
                    continue;
                }

                // commit
                $mapper->transactionCommit();
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

    public function addOption(array $value)
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
