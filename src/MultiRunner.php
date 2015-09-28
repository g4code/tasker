<?php
namespace G4\Tasker;

class MultiRunner
{
    private $taskIds;

    private $exceptionMapper;

    private $taskMapper;

    /**
     * @param Model\Mapper\Mysql\Task $taskMapper
     * @param Model\Mapper\Mysql\TaskErrorLog $exceptionMapper
     */
    public function __construct(\G4\Tasker\Model\Mapper\Mysql\Task $taskMapper, \G4\Tasker\Model\Mapper\Mysql\TaskErrorLog $exceptionMapper)
    {
        $this->taskMapper = $taskMapper;
        $this->exceptionMapper = $exceptionMapper;
    }

    public function execute()
    {
        $tasks = [];

        foreach ($this->taskIds as $taskId) {
            try {
                $runner = new Runner($this->taskMapper, $this->exceptionMapper);
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