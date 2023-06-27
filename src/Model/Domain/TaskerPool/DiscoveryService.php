<?php

namespace G4\Tasker\Model\Domain\TaskerPool;

class DiscoveryService
{
    /**
     * @var TaskerPoolRepository
     */
    private $repo;

    public function __construct(TaskerPoolRepository $repo)
    {
        $this->repo = $repo;
    }

    /**
     * @return void
     */
    public function add()
    {
        $hostname = gethostname();
        if ($hostname) {
            $this->repo->upsert($hostname);
        }
    }

    public function getAvailableHostnames()
    {
        return  implode('|', $this->repo->getAvailableHostnames());
    }
}
