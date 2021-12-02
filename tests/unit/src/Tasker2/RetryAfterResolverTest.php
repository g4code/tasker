<?php


class RetryAfterResolverTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var array
     */
    private $delayForRetries;

    /**
     * @var \G4\Tasker\Tasker2\RetryAfterResolver
     */
    private $resolverDelay;

    /**
     * Set up method
     */
    protected function setUp()
    {
        $this->delayForRetries = [
            1 => 10,
            2 => 10,
            3 => 20,
            4 => 60,
            5 => 120,
            6 => 600,
            7 => 1800,
            8 => 86400
        ];

        $this->resolverDelay = new \G4\Tasker\Tasker2\RetryAfterResolver($this->delayForRetries);
    }

    /**
     * Tear down method
     */
    protected function tearDown()
    {
        $this->delayForRetries = null;
        $this->resolverDelay = null;
    }

    /**
     * @dataProvider resolverDataProvider
     */
    public function testResolve($startedCount, $retryAfter)
    {
        $resolver = new \G4\Tasker\Tasker2\RetryAfterResolver();

        $this->assertSame($retryAfter, $resolver->resolve($startedCount));
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
        $this->assertSame($retryAfter, $this->resolverDelay->resolve($startedCount));
    }

    public function testGetMaxRetryAttempts()
    {
        $this->assertSame(count($this->delayForRetries), $this->resolverDelay->getMaxRetryAttempts());
    }

    public function testGetDelayForRetries()
    {
        $this->assertSame($this->delayForRetries, $this->resolverDelay->getDelayForRetries());
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