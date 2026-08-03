<?php

use G4\Tasker\Tasker2\Profiler\RabbitMq\Ticker;

class TickerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Ticker
     */
    private $ticker;

    protected function setUp(): void
    {
        $this->ticker = Ticker::getInstance();
        $this->ticker->clear();
    }

    protected function tearDown(): void
    {
        $this->ticker->clear();
        $this->ticker = null;
    }

    public function testGetNameAndType()
    {
        $this->assertEquals('rabbitmq', $this->ticker->getName());
        $this->assertEquals('rabbitmq', $this->ticker->getType());
    }

    public function testGetDataFormatterInstance()
    {
        $this->assertInstanceOf(
            '\G4\Tasker\Tasker2\Profiler\RabbitMq\Formatter',
            $this->ticker->getDataFormatterInstance()
        );
    }

    public function testFormatsRabbitMqOperationDetails()
    {
        $tickerId = $this->ticker->start();

        $this->ticker
            ->setOperation($tickerId, 'rabbitmq_channel_open')
            ->setHost($tickerId, 'rabbitmq.internal')
            ->setPort($tickerId, 5672)
            ->setConnectionStatus($tickerId, 'failed')
            ->setConnectionError($tickerId, 'RuntimeException: Connection refused')
            ->setTasks($tickerId, [
                [
                    'task_name' => 'Task\\Example',
                    'parameters' => ['user_id' => 123],
                ],
            ])
            ->end($tickerId);

        $formatted = $this->ticker->getFormatted();
        $query = reset($formatted['queries']);

        $this->assertEquals(1, $formatted['total_number_of_queries']);
        $this->assertEquals('rabbitmq_channel_open', $query['operation']);
        $this->assertEquals('rabbitmq.internal', $query['host']);
        $this->assertEquals(5672, $query['port']);
        $this->assertEquals('failed', $query['connection_status']);
        $this->assertEquals('RuntimeException: Connection refused', $query['connection_error']);
        $this->assertEquals('Task\\Example', $query['tasks'][0]['task_name']);
        $this->assertEquals(['user_id' => 123], $query['tasks'][0]['parameters']);
    }
}
