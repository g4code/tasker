<?php


class RetryAfterResolverTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider resolverDataProvider
     */
    public function testResolve($startedCount, $retryAfter)
    {
        $resolver = new \G4\Tasker\Tasker2\RetryAfterResolver($startedCount);

        $this->assertSame($retryAfter, $resolver->resolve());
    }

    public function resolverDataProvider()
    {
        return [
            [1, 60],
            [2, 300],
            [3, 1000]
        ];
    }

    /**
     * @dataProvider resolverDelayForRetriesDataProvider
     */
    public function testResolveDelayForRetries($startedCount, $retryAfter)
    {
        $delayForRetries = [
            1 => 10,
            2 => 10,
            3 => 20,
            4 => 60,
            5 => 120,
            6 => 600,
            7 => 1800,
            8 => 86400
        ];
        $resolverDelay = new \G4\Tasker\Tasker2\RetryAfterResolver($startedCount, $delayForRetries);

        $this->assertSame($retryAfter, $resolverDelay->resolve());
    }

    public function resolverDelayForRetriesDataProvider()
    {
        return [
            [1, 10],
            [2, 10],
            [3, 20],
            [4, 60],
            [5, 120],
            [6, 600],
            [7, 1800],
            [8, 86400]
        ];
    }
}