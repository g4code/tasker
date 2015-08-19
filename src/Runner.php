<?php
namespace G4\Tasker;

declare(ticks = 1);

use G4\Tasker\Model\Mapper\Mysql\Task as TaskMapper;
use G4\Log\Writer;

class Runner extends TimerAbstract
{
    private $taskMapper;

    private $taskId;

    private $taskData;

    private $alarmTime = 10;

    private $maxExecTime = 30;

    private $posixPid;

    public function __construct()
    {
        $this->timerStart();
        $this->taskMapper = new TaskMapper;

        $this->posixPid = posix_getpid();

        pcntl_signal(SIGALRM, [$this, "signalsHandler"], true);
        pcntl_alarm($this->alarmTime);
    }

    public function signalsHandler($signal)
    {
        // set alarm again for next run
        pcntl_alarm($this->alarmTime);

        /**
         * check if task is done by checking status in database...
         * for some reason we have zombie processes that finish what they are intended to do but don't kill process in memory
         */
        if($this->checkIsTaskFinished()) {
            // do something here
        }

        // sanity check, if time reached kill process
        if($this->getRunningTime() > $this->maxExecTime) {
            Writer::writeLogPre($this->taskData, 'tasker_kill');
            posix_kill($this->posixPid, SIGUSR1);
        }
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
        $mapper = new TaskMapper;

        try {
            $this
                ->fetchTaskData()
                ->updateToWorking()
                ->executeTask()
                ->timerStop()
                ->updateToDone();

        } catch (\Exception $e) {
            Writer::writeLogPre($e, 'tasker_runner_exception');
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
     */
    private function fetchTaskData()
    {
        $identity = $this->taskMapper->getIdentity();

        $identity
            ->field('task_id')
            ->eq($this->getTaskId());

        $this->taskData = $this->taskMapper->findOne($identity);
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
}