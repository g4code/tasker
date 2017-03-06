<?php
namespace G4\Tasker;

class MultiRunner
{
    private $taskIds;

    private $errorRepository;

    private $taskRepository;

    /**
     * @param Model\Repository\TaskRepositoryInterface $taskRepository
     * @param Model\Repository\ErrorRepositoryInterface $errorRepository
     */
    public function __construct(\G4\Tasker\Model\Repository\TaskRepositoryInterface $taskRepository, \G4\Tasker\Model\Repository\ErrorRepositoryInterface $errorRepository)
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

    public function setTaskIds($value)
    {
        $this->taskIds = is_array($value)
            ? $value
            : json_decode($value);
        return $this;
    }
}