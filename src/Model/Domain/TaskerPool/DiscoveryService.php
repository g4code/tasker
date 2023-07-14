<?php

namespace G4\Tasker\Model\Domain\TaskerPool;

use G4\Tasker\Identifier;

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
    public function notify()
    {
        $hostname = gethostname();
        if ($hostname) {
            $this->repo->upsert($hostname);
        }
    }

    /**
     * @return string
     */
    public function getAvailableHostnames()
    {
        return  implode(Identifier::DELIMITER, $this->getAvailableHostnamesAsArray());
    }

    /**
     * @return array
     */
    public function getAvailableHostnamesAsArray()
    {
        return $this->repo->getAvailableHostnames();
    }
}
