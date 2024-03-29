<?php

use G4\Tasker\Tasker2\RetryAfterResolver;

class RetryAfterResolverTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var array
     */
    private $delayForRetries;

    /**
     * @var RetryAfterResolver
     */
    private $resolverDelay;

    /**
     * @var RetryAfterResolver
     */
    private $resolverDelayEmptyArray;

    /**
     * @var RetryAfterResolver
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

        $this->resolverDelay = new RetryAfterResolver($this->delayForRetries);
        $this->resolverDelayEmptyArray = new RetryAfterResolver([]);
        $this->resolverDelayNotSet = new RetryAfterResolver();
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
        $resolver = new RetryAfterResolver();

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
        $this->assertEquals(count($this->delayForRetries), $this->resolverDelay->getMaxRetryAttempts());
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
        $this->assertEquals(count(RetryAfterResolver::DEFAULT_DELAY), $this->resolverDelayNotSet->getMaxRetryAttempts());
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
        $this->assertEquals(count(RetryAfterResolver::DEFAULT_DELAY), $this->resolverDelayEmptyArray->getMaxRetryAttempts());
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