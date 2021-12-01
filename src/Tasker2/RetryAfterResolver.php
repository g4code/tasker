<?php

namespace G4\Tasker\Tasker2;

class RetryAfterResolver
{
    const RETRY_AFTER_60 = 60;
    const RETRY_AFTER_300 = 300;
    const RETRY_AFTER_1000 = 1000;

    /**
     * @var int
     */
    private $startedCount;

    /**
     * @var array
     */
    private $delayForRetries;

    /**
     * RetryAfterResolver constructor.
     * @param int $startedCount
     * @param array $delayForRetries
     */
    public function __construct($startedCount, array $delayForRetries = [])
    {
        $this->startedCount = (int)$startedCount;
        $this->delayForRetries = $delayForRetries;
    }

    /**
     * Return after how much seconds task should be retried based on number of starting
     *
     * @return int
     */
    public function resolve()
    {
        if (!empty($this->delayForRetries)) {
            return isset($this->delayForRetries[$this->startedCount])
                ? $this->delayForRetries[$this->startedCount]
                : self::RETRY_AFTER_60;
        }

        if ($this->startedCount === 2) {
            return self::RETRY_AFTER_300;
        }

        if ($this->startedCount === 3) {
            return self::RETRY_AFTER_1000;
        }

        return self::RETRY_AFTER_60;
    }
}