<?php
namespace G4\Tasker\Model\Domain;

class Task
{
    /**
     * @var int
     */
    private $taskId;

    /**
     * @var int
     */
    private $recuId;

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
    private $status;

    /**
     * @var int
     */
    private $priority;

    /**
     * @var int
     */
    private $tsCreated;

    /**
     * @var int
     */
    private $tsStarted;

    /**
     * @var float
     */
    private $execTime;

    /**
     * @var int
     */
    private $startedCount;

    /**
     * Task constructor.
     * @param int $taskId
     * @param int $recuId
     * @param string $identifier
     * @param string $task
     * @param string $data
     * @param int $status
     * @param int $priority
     * @param int $tsCreated
     * @param int $tsStarted
     * @param float $execTime
     * @param int $startedCount
     */
    public function __construct($taskId, $recuId, $identifier, $task, $data, $status, $priority, $tsCreated, $tsStarted, $execTime, $startedCount)
    {
        $this->taskId = $taskId;
        $this->recuId = $recuId;
        $this->identifier = $identifier;
        $this->task = $task;
        $this->data = $data;
        $this->status = $status;
        $this->priority = $priority;
        $this->tsCreated = $tsCreated;
        $this->tsStarted = $tsStarted;
        $this->execTime = $execTime;
        $this->startedCount = $startedCount;
    }

    public function getRawData()
    {
        return array(
            'task_id'        => $this->getTaskId(),
            'recu_id'        => $this->getRecurringId(),
            'identifier'     => $this->getIdentifier(),
            'task'           => $this->getTask(),
            'data'           => $this->getData(),
            'status'         => $this->getStatus(),
            'priority'       => $this->getPriority(),
            'ts_created'     => $this->getTsCreated(),
            'ts_started'     => $this->getTsStarted(),
            'exec_time'      => $this->getExecTime(),
            'started_count'  => $this->getStartedCount(),
        );
    }



    /**
     * @param $taskId int
     * @return $this
     */
    public function setTaskId($taskId)
    {
        $this->taskId = $taskId;
        return $this;
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
    public function getTask()
    {
        return $this->task;
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
    public function getRecurringId()
    {
        return $this->recuId;
    }

    /**
     * @return int
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @return int
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @return int
     */
    public function getPriority()
    {
        return $this->priority;
    }

    /**
     * @return int
     */
    public function getTsCreated()
    {
        return $this->tsCreated;
    }

    /**
     * @return int
     */
    public function getTsStarted()
    {
        return $this->tsStarted;
    }

    /**
     * @return int
     */
    public function getExecTime()
    {
        return $this->execTime;
    }

    /**
     * @return int
     */
    public function getStartedCount()
    {
        return $this->startedCount;
    }

    /**
     * @return \G4\Tasker\Model\Domain\Task
     */
    public function setTask($value)
    {
        $this->task = $value;
        return $this;
    }

    /**
     * @return \G4\Tasker\Model\Domain\Task
     */
    public function setIdentifier($value)
    {
        $this->identifier = $value;
        return $this;
    }

    /**
     * @return \G4\Tasker\Model\Domain\Task
     */
    public function setRecurringId($value)
    {
        $this->recuId = $value;
        return $this;
    }

    /**
     * @return \G4\Tasker\Model\Domain\Task
     */
    public function setData($value)
    {
        $this->data = $value;
        return $this;
    }

    /**
     * @return \G4\Tasker\Model\Domain\Task
     */
    public function setStatus($value)
    {
        $this->status = $value;
        return $this;
    }

    /**
     * @return \G4\Tasker\Model\Domain\Task
     */
    public function setPriority($value)
    {
        $this->priority = $value;
        return $this;
    }

    /**
     * @return \G4\Tasker\Model\Domain\Task
     */
    public function setTsCreated($value)
    {
        $this->tsCreated = $value;
        return $this;
    }

    /**
     * @return \G4\Tasker\Model\Domain\Task
     */
    public function setTsStarted($value)
    {
        $this->tsStarted = $value;
        return $this;
    }

    /**
     * @return \G4\Tasker\Model\Domain\Task
     */
    public function setExecTime($value)
    {
        $this->execTime = $value;
        return $this;
    }

    /**
     * @return \G4\Tasker\Model\Domain\Task
     */
    public function setStartedCount($value)
    {
        $this->startedCount = $value;
        return $this;
    }

    public function setStatusBroken()
    {
        $this->status = \G4\Tasker\Consts::STATUS_BROKEN;
        return $this;
    }

    /**
     * @param $data
     * @return Task
     */
    public static function fromData($data)
    {
        return new self(
            (int) $data['task_id'],
            (int) $data['recu_id'],
            $data['identifier'],
            $data['task'],
            $data['data'],
            (int) $data['status'],
            (int) $data['priority'],
            (int) $data['ts_created'],
            (int) $data['ts_started'],
            (int) $data['exec_time'],
            (int) $data['started_count']
        );
    }

}
