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
     * RetryAfterResolver constructor.
     * @param int $startedCount
     */
    public function __construct($startedCount)
    {
        $this->startedCount = (int) $startedCount;
    }

    /**
     * Return after how much seconds task should be retried based on number of starting
     *
     * @return int
     */
    public function resolve()
    {
        if ($this->startedCount === 2) {
            return self::RETRY_AFTER_300;
        }

        if ($this->startedCount === 3) {
            return self::RETRY_AFTER_1000;
        }

        return self::RETRY_AFTER_60;
    }
}