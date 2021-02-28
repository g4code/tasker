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
}