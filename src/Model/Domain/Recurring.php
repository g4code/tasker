<?php
namespace G4\Tasker\Model\Domain;


class Recurring
{
    /**
     * @var int
     */
    private $recuId;

    /**
     * @var string
     */
    private $frequency;

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
     * Recurring constructor.
     * @param int $recuId
     * @param string $frequency
     * @param string $task
     * @param string $data
     * @param int $status
     * @param int $priority
     */
    public function __construct($recuId, $frequency, $task, $data, $status, $priority)
    {
        $this->recuId = $recuId;
        $this->frequency = $frequency;
        $this->task = $task;
        $this->data = $data;
        $this->status = $status;
        $this->priority = $priority;
    }


    public function getRawData()
    {
        return array(
            'recu_id'        => $this->getRecuId(),
            'task'           => $this->getTask(),
            'frequency'      => $this->getFrequency(),
            'data'           => $this->getData(),
            'status'         => $this->getStatus(),
            'priority'       => $this->getPriority(),
        );
    }

    /**
     * @return int
     */
    public function getRecuId()
    {
        return $this->recuId;
    }

    /**
     * @return string
     */
    public function getTask()
    {
        return $this->task;
    }

    /**
     * @return int
     */
    public function getFrequency()
    {
        return $this->frequency;
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
     * @param int $recuId
     */
    public function setRecuId($recuId)
    {
        $this->recuId = $recuId;
        return $this;
    }

    /**
     * @return \G4\Tasker\Model\Domain\Recurring
     */
    public function setTask($value)
    {
        $this->task = $value;
        return $this;
    }

    /**
     * @return \G4\Tasker\Model\Domain\Recurring
     */
    public function setFrequency($value)
    {
        $this->frequency = $value;
        return $this;
    }

    /**
     * @return \G4\Tasker\Model\Domain\Recurring
     */
    public function setData($value)
    {
        $this->data = $value;
        return $this;
    }

    /**
     * @return \G4\Tasker\Model\Domain\Recurring
     */
    public function setStatus($value)
    {
        $this->status = $value;
        return $this;
    }

    /**
     * @return \G4\Tasker\Model\Domain\Recurring
     */
    public function setPriority($value)
    {
        $this->priority = $value;
        return $this;
    }

    /**
     * @param array $data
     * @return Recurring
     */
    public static function fromData($data)
    {
        return new self(
            (int) $data['recu_id'],
            $data['frequency'],
            $data['task'],
            $data['data'],
            (int) $data['status'],
            (int) $data['priority']
        );
    }
}
