<?php
namespace G4\Tasker;

use G4\Tasker\Model\Domain\Task;
use G4\Tasker\Model\Mapper\Mysql\Task as TaskMapper;

class Forker
{
    private $_context = 'php';

    private $_runner;

    private $_env;

    public function run(Task $task)
    {
        $mapper = new TaskMapper;

        $task->addMapper($mapper);

        // begin transaction
        $mapper->transactionBegin();

//         mark task as working
//         $task->setStatus(Consts::STATUS_WORKING);
//         $task->save();

        $taskOptions = array(
            'id'  => $task->getId(),
            'env' => $this->getEnvironment(),
        );

        try {
            // fork process
            $this->_fork($taskOptions);
        } catch (\Exception $e) {
            // rollback
            $mapper->transactionRollback();
            return false;
        }

        // commit
        $mapper->transactionCommit();
        return true;
    }

    private function _fork($options)
    {
        $pid = pcntl_fork();

        if ($pid == -1) {
            throw new \Exception('could not fork');
        } else if ($pid) {
            // parent process
        } else {
            $cmd = sprintf('%s %s %s', $this->_context, $this->_runner, $this->_formatOptions($options));
            echo shell_exec($cmd), PHP_EOL;
            exit(0);
        }
    }

    private function _formatOptions($options)
    {
        foreach($options as $key => $value) {
            $segments[] = "--{$key} {$value}";
        }

        return implode(' ', $segments);
    }

    public function getRunner()
    {
        if(null === $this->_runner) {
            throw new \Exception('Runner is not set');
        }
        return $this->_runner;
    }

    public function setRunner($value)
    {
        $this->_runner = $value;
        return $this;
    }

    public function getEnvironment()
    {
        if(null === $this->_env) {
            throw new \Exception('Environment is not set');
        }
        return $this->_env;
    }

    public function setEnvironment($value)
    {
        $this->_env = $value;
        return $this;
    }

}