<?php

namespace G4\Tasker\Tasker2;

use G4\Tasker\Model\Domain\TaskerPool\DiscoveryService;
use G4\Tasker\Model\Repository\Mysql\TaskRepository;
use G4\Tasker\Tasker2\Exception\NoAvailableHostsException;

class RebalanceService
{
    /**
     * @var DiscoveryService
     */
    private $discoveryService;

    /**
     * @var TaskRepository
     */
    private $taskRepository;

    /**
     * @var \G4\Log\ErrorLogger
     */
    private $errorLogger;

    public function __construct(DiscoveryService $discoveryService, TaskRepository $taskRepository)
    {
        $this->discoveryService = $discoveryService;
        $this->taskRepository = $taskRepository;
    }

    public function setErrorLogger(\G4\Log\ErrorLogger $errorLogger)
    {
        $this->errorLogger = $errorLogger;
        return $this;
    }

    /**
     * @return void
     */
    public function rebalance()
    {
        $availableHostnames = $this->discoveryService->getAvailableHostnamesAsArray();

        if (count($availableHostnames) === 0) {
            $this->errorLogger !== null && $this->errorLogger->log(new NoAvailableHostsException(
                'No available hosts found for ' . __CLASS__ . __METHOD__
            ));
            return;
        }

        $tasksForRebalance = $this->taskRepository->findTasksForRebalance($availableHostnames);
        if (count($tasksForRebalance) === 0) {
            return;
        }

        foreach ($tasksForRebalance as $taskId) {
            $index = array_rand($availableHostnames);
            $tasks[$availableHostnames[$index]][] = (int) $taskId;
        }

        print("Tasks rebalance results:\n");
        foreach ($tasks as $identifier => $taskIds) {
            $this->taskRepository->updateIdentifier($identifier, $taskIds);
            printf(
                "[%s] - Hostname %s rebalanced %d tasks to %s\n",
                date('Y-m-d H:i:s'),
                gethostname(),
                count($taskIds),
                $identifier
            );
        }
    }
}
