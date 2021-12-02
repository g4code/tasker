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
     * @var \G4\Tasker\Tasker2\RetryAfterResolver
     */
    private $resolverDelayEmptyArray;

    /**
     * @var \G4\Tasker\Tasker2\RetryAfterResolver
     */
    private $resolverDelayNotSet;

    /**
     * Set up method
     */
    protected function setUp()
    {
        $this->delayForRetries = [
            10,
            10,
            20,
            60,
            120,
            600,
            1800,
            86400
        ];

        $this->resolverDelay = new \G4\Tasker\Tasker2\RetryAfterResolver($this->delayForRetries);
        $this->resolverDelayEmptyArray = new \G4\Tasker\Tasker2\RetryAfterResolver([]);
        $this->resolverDelayNotSet = new \G4\Tasker\Tasker2\RetryAfterResolver();
    }

    /**
     * Tear down method
     */
    protected function tearDown()
    {
        $this->delayForRetries = null;
        $this->resolverDelay = null;
        $this->resolverDelayNotSet = null;
        $this->resolverDelayEmptyArray = null;
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
        $testArray = [
            1 => 10,
            2 => 10,
            3 => 20,
            4 => 60,
            5 => 120,
            6 => 600,
            7 => 1800,
            8 => 86400
        ];
        $this->assertSame($testArray, $this->resolverDelay->getDelayForRetries());
    }

    public function testDelayForRetriesNotSet()
    {
        $testArray = [
            1 => 60,
            2 => 300,
            3 => 1000
        ];

        $this->assertSame($testArray, $this->resolverDelayNotSet->getDelayForRetries());
    }

    public function testMaxCountDelayForRetriesNotSet()
    {
        $defaultDelay = [
            60,
            300,
            1000
        ];

        $this->assertSame(count($defaultDelay), $this->resolverDelayNotSet->getMaxRetryAttempts());
    }

    public function testDelayForRetriesEmptyArray()
    {
        $testArray = [
            1 => 60,
            2 => 300,
            3 => 1000
        ];

        $this->assertSame($testArray, $this->resolverDelayEmptyArray->getDelayForRetries());
    }

    public function testMaxCountDelayForRetriesEmptyArray()
    {
        $defaultDelay = [
            60,
            300,
            1000
        ];

        $this->assertSame(count($defaultDelay), $this->resolverDelayEmptyArray->getMaxRetryAttempts());
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