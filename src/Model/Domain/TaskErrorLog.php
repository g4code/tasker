<?php

namespace G4\Tasker\Model\Domain;

class TaskErrorLog
{

    /**
     * @var int
     */
    private $telId;

    /**
     * @var int
     */
    private $taskId;

    /**
     * @var string
     */
    private $identifier;

    /**
     * @var string
     */
    private $task;

    /**
     * @var string
     */
    private $data;

    /**
     * @var int
     */
    private $tsStarted;

    /**
     * @var string
     */
    private $dateStarted;

    /**
     * @var int
     */
    private $execTime;

    /**
     * @var
     */
    private $log;

    /**
     * TaskErrorLog constructor.
     * @param int $telId
     * @param int $taskId
     * @param string $identifier
     * @param string $task
     * @param string $data
     * @param int $tsStarted
     * @param string $dateStarted
     * @param int $execTime
     * @param $log
     */
    public function __construct($taskId, $identifier, $task, $data, $tsStarted, $dateStarted, $execTime, $log)
    {
        $this->taskId = $taskId;
        $this->identifier = $identifier;
        $this->task = $task;
        $this->data = $data;
        $this->tsStarted = $tsStarted;
        $this->dateStarted = $dateStarted;
        $this->execTime = $execTime;
        $this->log = $log;
    }


    /**
     * @return array
     */
    public function getRawData()
    {
        return [
            'tel_id'         => $this->getTelId(),
            'task_id'        => $this->getTaskId(),
            'identifier'     => $this->getIdentifier(),
            'task'           => $this->getTask(),
            'data'           => $this->getData(),
            'ts_started'     => $this->getTsStarted(),
            'date_started'   => $this->getDateStarted(),
            'exec_time'      => $this->getExecTime(),
            'log'            => $this->getLog(),
        ];
    }

    /**
     * @return int
     */
    public function getTelId()
    {
        return $this->telId;
    }

    /**
     * @return int
     */
    public function getTaskId()
    {
        return $this->taskId;
    }

    /**
     * @return string
     */
    public function getIdentifier()
    {
        return $this->identifier;
    }

    /**
     * @return string
     */
    public function getTask()
    {
        return $this->task;
    }

    /**
     * @return string
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @return int
     */
    public function getTsStarted()
    {
        return $this->tsStarted;
    }

    /**
     * @return string
     */
    public function getDateStarted()
    {
        return $this->dateStarted;
    }

    /**
     * @return int
     */
    public function getExecTime()
    {
        return $this->execTime;
    }

    /**
     * @return mixed
     */
    public function getLog()
    {
        return json_decode($this->log, true);
    }

    /**
     * @param int $telId
     * @return $this
     */
    public function setTelId($telId)
    {
        $this->telId = $telId;
        return $this;
    }

    /**
     * @param $taskId
     * @return $this
     */
    public function setTaskId($taskId)
    {
        $this->taskId = $taskId;
        return $this;
    }

    /**
     * @param $identifier
     * @return $this
     */
    public function setIdentifier($identifier)
    {
        $this->identifier = $identifier;
        return $this;
    }

    /**
     * @param $task
     * @return $this
     */
    public function setTask($task)
    {
        $this->task = $task;
        return $this;
    }

    /**
     * @param $data
     * @return $this
     */
    public function setData($data)
    {
        $this->data = $data;
        return $this;
    }

    /**
     * @param $ts_started
     * @return $this
     */
    public function setTsStarted($ts_started)
    {
        $this->tsStarted = $ts_started;
        return $this;
    }

    /**
     * @param $date_started
     * @return $this
     */
    public function setDateStarted($date_started)
    {
        $this->dateStarted = $date_started;
        return $this;
    }

    /**
     * @param $exec_time
     * @return $this
     */
    public function setExecTime($exec_time)
    {
        $this->execTime = $exec_time;
        return $this;
    }

    /**
     * @param $log
     * @return $this
     */
    public function setLog($log)
    {
        $this->log = json_encode($log);
        return $this;
    }

    /**
     * @return TaskErrorLog
     */
    public static function fromTask(Task $task, $dateStarted, $execTime, $log)
    {
        return new self(
            $task->getTaskId(),
            $task->getIdentifier(),
            $task->getTask(),
            $task->getData(),
            $task->getTsStarted(),
            $dateStarted,
            $execTime,
            $log
        );
    }

}