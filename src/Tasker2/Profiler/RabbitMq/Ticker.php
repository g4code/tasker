<?php

namespace G4\Tasker\Tasker2\Profiler\RabbitMq;

class Ticker extends \G4\Profiler\Ticker\TickerAbstract
{
    const NAME = 'rabbitmq';
    const TYPE = 'rabbitmq';

    /**
     * @var Ticker
     */
    private static $instance;

    private function __construct()
    {
    }

    private function __clone()
    {
    }

    /**
     * @return Ticker
     */
    final public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new static();
        }
        return self::$instance;
    }

    /**
     * @return Formatter
     */
    public function getDataFormatterInstance()
    {
        return new Formatter();
    }

    /**
     * @return string
     */
    public function getName()
    {
        return self::NAME;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return self::TYPE;
    }

    /**
     * @param string $uniqueId
     * @param string $operation
     * @return Ticker
     */
    public function setOperation($uniqueId, $operation)
    {
        $this->getDataPart($uniqueId)->setOperation($operation);
        return self::$instance;
    }

    /**
     * @param string $uniqueId
     * @param string $host
     * @return Ticker
     */
    public function setHost($uniqueId, $host)
    {
        $this->getDataPart($uniqueId)->setHost($host);
        return self::$instance;
    }

    /**
     * @param string $uniqueId
     * @param int $port
     * @return Ticker
     */
    public function setPort($uniqueId, $port)
    {
        $this->getDataPart($uniqueId)->setPort($port);
        return self::$instance;
    }

    /**
     * @param string $uniqueId
     * @param string $connectionStatus
     * @return Ticker
     */
    public function setConnectionStatus($uniqueId, $connectionStatus)
    {
        $this->getDataPart($uniqueId)->setConnectionStatus($connectionStatus);
        return self::$instance;
    }

    /**
     * @param string $uniqueId
     * @param string $connectionError
     * @return Ticker
     */
    public function setConnectionError($uniqueId, $connectionError)
    {
        $this->getDataPart($uniqueId)->setConnectionError($connectionError);
        return self::$instance;
    }

    /**
     * @param string $uniqueId
     * @param array $tasks
     * @return Ticker
     */
    public function setTasks($uniqueId, array $tasks)
    {
        $this->getDataPart($uniqueId)->setTasks($tasks);
        return self::$instance;
    }
}
