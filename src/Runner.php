<?php
namespace G4\Tasker;

declare(ticks = 1);

use G4\Tasker\Model\Mapper\Mysql\Task as taskRepository;
use G4\Log\Writer;

class Runner extends TimerAbstract
{

    /**
     * @var \G4\Tasker\Model\Domain\Task
     */
    private $taskData;

    private $taskId;

    private $exceptionMapper;

    /**
     * @var \G4\Tasker\Model\Repository\TaskRepositoryInterface
     */
    private $taskRepository;


    public function __construct(\G4\Tasker\Model\Repository\TaskRepositoryInterface $taskRepository, \G4\Tasker\Model\Mapper\Mysql\TaskErrorLog $exceptionMapper)
    {
        $this->taskRepository = $taskRepository;
        $this->timerStart();
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
        $this->taskId = (int) $value;
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
        $this->taskData = $this->taskRepository->find($this->getTaskId());

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
        $this->taskRepository->update($this->taskData);
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
        $this->taskRepository->update($this->taskData);
        return $this;
    }

    private function updateToMultiWorking()
    {
        $this->taskData
            ->setStatus(Consts::STATUS_MULTI_WORKING)
            ->setTsStarted(time());
        $this->taskRepository->update($this->taskData);
        return $this;
    }

    private function updateToBroken()
    {
        $this->taskData->setStatusBroken();
        $this->taskData->setExecTime($this->getTotalTime());
        $this->taskRepository->update($this->taskData);
        return $this;
    }

    /**
     * @todo: Dejan: remove duplicated code, uses the same logic as fetchTaskData()
     * @return boolean
     */
    private function checkIsTaskFinished()
    {
        $identity = $this->taskRepository->getIdentity();

        $identity
            ->field('task_id')
            ->eq($this->getTaskId());

        $taskData = $this->taskRepository->findOne($identity);

        return $taskData->getStatus() == Consts::STATUS_DONE;
    }

    public function handleException(\Exception $e)
    {
        // because register_shutdown_function is registered multiple times inside MultiRunner, it is also called
        // multiple times in case of FATAL error. So this condition will ensure that only currently executing/failed
        // task will be updated to STATUS_BROKEN
        if ($this->taskData->getStatus() != Consts::STATUS_WORKING) {
            return $this;
        }
    
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