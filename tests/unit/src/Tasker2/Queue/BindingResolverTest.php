<?php

use G4\Tasker\Tasker2\MessageOptions;
use G4\Tasker\Tasker2\Queue\BindingResolver;
use G4\ValueObject\StringLiteral;

class BindingResolverTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var MessageOptions
     */
    private $messageOptions;

    /**
     * @var BindingResolver
     */
    private $resolver;

    /**
     * Set up method
     */
    protected function setUp()
    {
        $this->messageOptions = $this->createMock(MessageOptions::class);

        $this->resolver = new BindingResolver($this->messageOptions);
    }

    /**
     * Tear down method
     */
    protected function tearDown()
    {
        $this->messageOptions = null;

        $this->resolver = null;
    }

    public function testAdditionalBindingReturned()
    {
        $task = new StringLiteral('task');
        $binding = 'task_binding';
        $additionalBindings = new \G4\ValueObject\Dictionary([(string) $task => $binding]);
        $message = new \G4\ValueObject\Dictionary(['foo' => 'bar']);

        $this->messageOptions
            ->expects(self::once())
            ->method('getAdditionalBindings')
            ->willReturn($additionalBindings);

        $result = $this->resolver->resolve($message, $task);
        self::assertSame($binding, $result);
    }


    public function testDefaultBindingReturned()
    {
        $task = new StringLiteral('task');
        $binding = 'task_binding';
        $additionalBindings = new \G4\ValueObject\Dictionary(['other_task' => 'other_binding']);
        $message = new \G4\ValueObject\Dictionary(['foo' => 'bar']);

        $this->messageOptions
            ->expects(self::once())
            ->method('getAdditionalBindings')
            ->willReturn($additionalBindings);

        $this->messageOptions
            ->expects(self::once())
            ->method('getBinding')
            ->willReturn($binding);

        $result = $this->resolver->resolve($message, $task);
        self::assertSame($binding, $result);
    }

    public function testHPBindingReturned()
    {
        $task = new StringLiteral('task');
        $binding = 'task_binding';
        $additionalBindings = new \G4\ValueObject\Dictionary(['other_task' => 'other_binding']);
        $message = new \G4\ValueObject\Dictionary(['foo' => 'bar', 'priority' => 80]);

        $this->messageOptions
            ->expects(self::once())
            ->method('getAdditionalBindings')
            ->willReturn($additionalBindings);

        $this->messageOptions
            ->expects(self::once())
            ->method('hasBindingHP')
            ->willReturn(true);

        $this->messageOptions
            ->expects(self::once())
            ->method('getBindingHP')
            ->willReturn($binding);

        $result = $this->resolver->resolve($message, $task);
        self::assertSame($binding, $result);
    }
}