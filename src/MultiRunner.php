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

    /**
     * @param TaskRepositoryInterface $taskRepository
     * @param ErrorRepositoryInterface $errorRepository
     */
    public function __construct(TaskRepositoryInterface $taskRepository, ErrorRepositoryInterface $errorRepository)
    {
        $this->taskRepository = $taskRepository;
        $this->errorRepository = $errorRepository;
    }

    public function execute()
    {
        $tasks = [];

        foreach ($this->taskIds as $taskId) {
            try {
                $runner = new Runner($this->taskRepository, $this->errorRepository);
                $runner
                    ->setTaskId($taskId)
                    ->setMultiWorking();

                $tasks[] = $runner;
            }catch(\Exception $e) {
                print $e->getMessage() . "\n";  // todo petar: re-throw exception and catch it on upper level
            }
        }

        foreach ($tasks as $task) {
            $task->execute();
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
}