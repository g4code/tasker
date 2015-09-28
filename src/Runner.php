<?php
namespace G4\Tasker;

declare(ticks = 1);

use G4\Tasker\Model\Mapper\Mysql\Task as TaskMapper;
use G4\Log\Writer;

class Runner extends TimerAbstract
{
    /**
     * @var TaskMapper
     */
    private $taskMapper;

    /**
     * @var \G4\Tasker\Model\Domain\Task
     */
    private $taskData;

    private $taskId;

    private $exceptionMapper;


    public function __construct(\G4\Tasker\Model\Mapper\Mysql\Task $taskMapper, \G4\Tasker\Model\Mapper\Mysql\TaskErrorLog $exceptionMapper)
    {
        $this->timerStart();
        $this->taskMapper = $taskMapper;
        $this->exceptionMapper = $exceptionMapper;

        register_shutdown_function([$this, 'handleShutdownError']);
        set_error_handler([$this, 'handleError']);
    }

    public function getTaskId()
    {
        if(null === $this->taskId) {
            throw new \Exception('Task ID is not set');
        }
        return $this->taskId;
    }

    public function setTaskId($value)
    {
        $this->taskId = $value;
        return $this;
    }

    public function execute()
    {
        try {
            $this
                ->fetchTaskData()
                ->updateToWorking()
                ->executeTask()
                ->timerStop()
                ->updateToDone();

        } catch (\Exception $e) {
            Writer::writeLogPre($e, 'tasker_runner_exception');
            $this->handleException($e);
            throw $e;
        }
    }

    public function setMultiWorking()
    {
        $this
            ->fetchTaskData()
            ->updateToMultiWorking();
    }

    /**
     * @return \G4\Tasker\Runner
     */
    private function executeTask()
    {
        $this->getTaskInstance()
            ->setEncodedData($this->taskData->getData())
            ->execute();
        return $this;
    }

    /**
     * @return \G4\Tasker\Runner
     * @throws \Exception
     */
    private function fetchTaskData()
    {
        $identity = $this->taskMapper->getIdentity();

        $identity
            ->field('task_id')
            ->eq($this->getTaskId());

        $this->taskData = $this->taskMapper->findOne($identity);

        if (!$this->taskData instanceof \G4\Tasker\Model\Domain\Task) {
            throw new \Exception(sprintf("Task id='%d' does not exists.", $this->taskId));
        }

        return $this;
    }

    /**
     * @throws \Exception
     * @return \G4\Tasker\TaskAbstract
     */
    private function getTaskInstance()
    {
        $className = $this->taskData->getTask();

        if (class_exists($className) === false) {
            throw new \Exception("Class '{$className}' for task not found");
        }

        $task = new $className;

        if (!$task instanceof \G4\Tasker\TaskAbstract) {
            throw new \Exception("Class '{$className}' must extend \G4\Tasker\TaskAbstract class");
        }

        return $task;
    }

    /**
     * @return \G4\Tasker\Runner
     */
    private function updateToDone()
    {
        $this->taskData
            ->setStatus(Consts::STATUS_DONE)
            ->setExecTime($this->getTotalTime());
        $this->taskMapper->update($this->taskData);
        return $this;
    }

    /**
     * @return \G4\Tasker\Runner
     */
    private function updateToWorking()
    {
        $this->taskData
            ->setStatus(Consts::STATUS_WORKING)
            ->setTsStarted(time())
            ->setStartedCount($this->taskData->getStartedCount() + 1);
        $this->taskMapper->update($this->taskData);
        return $this;
    }

    private function updateToMultiWorking()
    {
        $this->taskData
            ->setStatus(Consts::STATUS_MULTI_WORKING)
            ->setTsStarted(time());
        $this->taskMapper->update($this->taskData);
        return $this;
    }

    private function updateToBroken()
    {
        $this->taskData
            ->setStatus(Consts::STATUS_BROKEN)
            ->setExecTime($this->getTotalTime());
        $this->taskMapper->update($this->taskData);
        return $this;
    }

    /**
     * @todo: Dejan: remove duplicated code, uses the same logic as fetchTaskData()
     * @return boolean
     */
    private function checkIsTaskFinished()
    {
        $identity = $this->taskMapper->getIdentity();

        $identity
            ->field('task_id')
            ->eq($this->getTaskId());

        $taskData = $this->taskMapper->findOne($identity);

        return $taskData->getStatus() == Consts::STATUS_DONE;
    }

    public function handleException(\Exception $e)
    {
        $this
            ->timerStop()
            ->updateToBroken();

        $eh = new \G4\Tasker\ExceptionHandler($this->getTaskId(), $this->taskData, $e, $this->getTotalTime(), $this->exceptionMapper);
        $eh->writeLog();
        return $this;
    }

    public function handleShutdownError()
    {
        $error = error_get_last();

        if (!in_array($error['type'], [ E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
            return $this;
        }

        $exception = new \ErrorException($error['message'], $error['type'], 1, $error['file'], $error['line']);
        $this->handleException($exception);
        return false;
    }

    public function handleError($errno, $errstr, $errfile, $errline)
    {
        $exception = new \ErrorException($errstr, $errno, 0, $errfile, $errline);
        $this->handleException($exception);
        return false;
    }
}