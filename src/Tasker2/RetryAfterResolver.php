<?php

namespace G4\Tasker\Tasker2;

class RetryAfterResolver
{
    const DEFAULT_DELAY = [
        60,
        300,
        1000
    ];

    /**
     * @var array
     */
    private $delayForRetries;

    /**
     * RetryAfterResolver constructor.
     * @param array $delayForRetries
     */
    public function __construct(array $delayForRetries = [])
    {
        $this->setDelayForRetries($delayForRetries);
    }

    /**
     * Return after how much seconds task should be retried based on number of starting
     *
     * @return int
     */
    public function resolve($startedCount)
    {
        return isset($this->delayForRetries[$startedCount])
            ? $this->delayForRetries[$startedCount]
            : self::DEFAULT_DELAY[0];
    }

    /**
     * @return array
     */
    public function getDelayForRetries()
    {
        return $this->delayForRetries;
    }

    /**
     * @return int
     */
    public function getMaxRetryAttempts()
    {
        return count($this->delayForRetries);
    }

    public function setDelayForRetries($delayForRetries)
    {
        $this->delayForRetries = count($delayForRetries)
            ? array_combine(range(1, count($delayForRetries)), $delayForRetries)
            : array_combine(range(1, count(self::DEFAULT_DELAY)), self::DEFAULT_DELAY);
    }
}
