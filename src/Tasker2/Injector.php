<?php

namespace G4\Tasker\Tasker2;

use G4\Tasker\Model\Repository\RecurringRepositoryInterface;
use G4\Tasker\Model\Repository\TaskRepositoryInterface;

class Injector
{
    /**
     * @var TaskRepositoryInterface
     */
    private $taskRepository;
    /**
     * @var RecurringRepositoryInterface
     */
    private $recurringRepository;

    /**
     * @var array
     */
    private $taskerHosts;

    /**
     * @var \G4\Log\Logger
     */
    private $logger;

    private $uniqueId;

    const LOG_TYPE = 'injector';

    /**
     * @var float
     */
    private $startTime;

    public function __construct(
        TaskRepositoryInterface $taskRepository,
        RecurringRepositoryInterface $recurringRepository,
        array $taskerHosts,
        \G4\Log\Logger $logger
    ) {
        $this->taskRepository = $taskRepository;
        $this->recurringRepository = $recurringRepository;
        $this->taskerHosts = $taskerHosts;
        $this->logger = $logger;
    }

    public function run()
    {
        $this->logStart();

        $injector = new \G4\Tasker\Injector(
            $this->taskRepository,
            $this->recurringRepository
        );
        $injector
            ->setHostname($this->taskerHosts)
            ->run();

        $this->logEnd();
    }

    private function logStart()
    {
        $this->uniqueId = md5(uniqid(microtime(), true));
        $this->startTime = microtime(true);
        $taskerStart = new \G4\Log\Data\TaskerStart();
        $taskerStart
            ->setId($this->uniqueId);
        $this->logger->log($taskerStart);
    }

    private function logEnd()
    {
        $taskerEnd = new \G4\Log\Data\TaskerEnd();
        $taskerEnd
            ->setId($this->uniqueId)
            ->setStartTime($this->startTime)
            ->setType(self::LOG_TYPE);
        $this->logger->logAppend($taskerEnd);
    }

}