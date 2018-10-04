<?php
namespace G4\Tasker;

use G4\Tasker\Model\Repository\ErrorRepositoryInterface;
use G4\Tasker\Model\Repository\TaskRepositoryInterface;

class MultiRunner
{
    /**
     * @var array
     */
    private $taskIds;

    /**
     * @var TaskRepositoryInterface
     */
    private $taskRepository;

    /**
     * @var ErrorRepositoryInterface
     */
    private $errorRepository;

    private $resourceContainer;

    /**
     * @var \G4\Log\Error\Exception
     */
    private $exceptionLogger;

    /**
     * @var \G4\Log\Logger
     */
    private $logger;

    /**
     * @var \ND\NewRelic\Transaction
     */
    private $newRelic;

    /**
     * @param TaskRepositoryInterface $taskRepository
     * @param ErrorRepositoryInterface $errorRepository
     */
    public function __construct(TaskRepositoryInterface $taskRepository, ErrorRepositoryInterface $errorRepository)
    {
        $this->taskRepository = $taskRepository;
        $this->errorRepository = $errorRepository;
    }

    public function setLogger(\G4\Log\Logger $logger)
    {
        $this->logger = $logger;
        return $this;
    }

    public function setNewRelic(\ND\NewRelic\Transaction $newRelic)
    {
        $this->newRelic = $newRelic;
        return $this;
    }

    public function execute()
    {
        $tasks = [];

        foreach ($this->taskIds as $taskId) {
            try {
                $runner = new Runner($this->taskRepository, $this->errorRepository);
                $runner
                    ->setLogger($this->logger)
                    ->setNewRelic($this->newRelic)
                    ->setTaskId($taskId)
                    ->setMultiWorking();

                $tasks[] = $runner;
            }catch(\Exception $e) {
                print $e->getMessage() . "\n";  // todo petar: re-throw exception and catch it on upper level
            }
        }

        foreach ($tasks as $task) {
            if ($this->hasResourceContainer()) {
                $task->setResourceContainer($this->getResourceContainer());
            }
            try {
                $task->execute();
            }catch (\Exception $e) {
                $this->logException($e);
            }
        }
    }

    /**
     * @param array|string $value Array or JSON array
     * @return $this
     */
    public function setTaskIds($value)
    {
        $this->taskIds = is_array($value)
            ? $value
            : json_decode($value);
        return $this;
    }

    public function getResourceContainer()
    {
        if($this->hasResourceContainer()){
            return $this->resourceContainer;
        }
        throw new \Exception('Resource container is missing');
    }

    public function hasResourceContainer()
    {
        return $this->resourceContainer != null;
    }

    public function setResourceContainer($resourceContainer)
    {
        $this->resourceContainer = $resourceContainer;
        return $this;
    }

    public function setExceptionLogger(\G4\Log\Error\Exception $exceptionLogger)
    {
        $this->exceptionLogger = $exceptionLogger;
        return $this;
    }

    private function logException(\Exception $e)
    {
        $this->exceptionLogger instanceof \G4\Log\Error\Exception && $this->exceptionLogger->handle($e);
    }
}