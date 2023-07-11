<?php

namespace G4\Tasker\Model\Domain\TaskerPool;

class TaskerPoolRepository
{
    use CachingTrait;

    const TABLE_NAME = 'tasker_pool';
    const INACTIVE = 0;
    const ACTIVE = 1;
    const HOST_ALIVE_SECONDS = 90;

    const CACHE_KEY = 'tasker_hostnames';

    /**
     * @var \PDO
     */
    private $pdo;
    /**
     * @var int
     */
    private $hostAvailabilityTime;

    /**
     * @var \G4\Mcache\Mcache
     */
    private $cache;

    public function __construct(\PDO $pdo, $hostAvailabilityTime = self::HOST_ALIVE_SECONDS)
    {
        $this->pdo = $pdo;
        $this->hostAvailabilityTime = $hostAvailabilityTime;
    }

    public function setCache(\G4\Mcache\Mcache $cache)
    {
        $this->cache = $cache;
        return $this;
    }

    /**
     * @param string $hostname
     */
    public function upsert($hostname)
    {
        $query = sprintf(
            "INSERT INTO %s (`hostname`,`status`,`ts_available`) VALUES ('%s',%s,%s) ON DUPLICATE KEY UPDATE ts_available=%s;",
            self::TABLE_NAME,
            $hostname,
            self::ACTIVE,
            time(),
            time()
        );

        $stmt = $this->pdo->prepare($query);

        if ($this->pdo->inTransaction()) {
            $stmt->execute();
            return;
        }

        $this->pdo->beginTransaction();
        $stmt->execute();
        $this->pdo->commit();
    }

    /**
     * @return array|null
     */
    public function getAvailableHostnames()
    {
        $cachedHostnames = $this->fetchFromCache();
        if ($cachedHostnames) {
            return $cachedHostnames;
        }

        $query = sprintf(
            "SELECT (hostname) FROM %s WHERE status = %s AND ts_available >= %s;",
            self::TABLE_NAME,
            self::ACTIVE,
            time() - $this->hostAvailabilityTime
        );

        $stmt = $this->pdo->prepare($query);
        $stmt->execute();

        $hostsArray =  array_map(function ($data) {
            return isset($data['hostname']) ? $data['hostname'] : null;
        }, $stmt->fetchAll());

        $hostnames = array_filter($hostsArray);
        $this->setToCache($hostnames);
        return $hostnames;
    }
}
