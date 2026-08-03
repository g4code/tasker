<?php

namespace G4\Tasker\Tasker2\Profiler\RabbitMq;

class Formatter extends \G4\Profiler\Ticker\Formatter
{
    /**
     * Local ID storage; parent property is private and uses uniqid(..., true).
     *
     * @var float|null
     */
    private $uniqid;

    /**
     * @var string
     */
    private $operation;

    /**
     * @var string
     */
    private $host;

    /**
     * @var int
     */
    private $port;

    /**
     * @var string
     */
    private $connectionStatus;

    /**
     * @var string
     */
    private $connectionError;

    /**
     * @var array
     */
    private $tasks;

    /**
     * @return array
     */
    public function getFormatted()
    {
        $formatted = parent::getFormatted() + [
            'operation' => $this->operation,
            'host'      => $this->host,
            'port'      => $this->port,
        ];

        if ($this->connectionStatus !== null) {
            $formatted['connection_status'] = $this->connectionStatus;
        }

        if ($this->connectionError !== null) {
            $formatted['connection_error'] = $this->connectionError;
        }

        if ($this->tasks !== null) {
            $formatted['tasks'] = $this->tasks;
        }

        return $formatted;
    }

    /**
     * @param string $operation
     * @return Formatter
     */
    public function setOperation($operation)
    {
        $this->operation = $operation;
        return $this;
    }

    /**
     * @param string $host
     * @return Formatter
     */
    public function setHost($host)
    {
        $this->host = $host;
        return $this;
    }

    /**
     * @param int $port
     * @return Formatter
     */
    public function setPort($port)
    {
        $this->port = $port;
        return $this;
    }

    /**
     * @param string $connectionStatus
     * @return Formatter
     */
    public function setConnectionStatus($connectionStatus)
    {
        $this->connectionStatus = $connectionStatus;
        return $this;
    }

    /**
     * @param string $connectionError
     * @return Formatter
     */
    public function setConnectionError($connectionError)
    {
        $this->connectionError = $connectionError;
        return $this;
    }

    /**
     * @param array $tasks
     * @return Formatter
     */
    public function setTasks(array $tasks)
    {
        $this->tasks = $tasks;
        return $this;
    }

    /**
     * Use microtime IDs so PHP does not cast uniqid(..., true) keys to float 0.
     *
     * @return string
     */
    public function getUniqId()
    {
        if ($this->uniqid === null) {
            $this->uniqid = microtime(true);
        }
        return sprintf('%.5f', $this->uniqid);
    }
}
