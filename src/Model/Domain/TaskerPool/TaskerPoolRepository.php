<?php

namespace G4\Tasker\Model\Domain\TaskerPool;

class TaskerPoolRepository
{
    const TABLE_NAME = 'tasker_pool';
    const INACTIVE = 0;
    const ACTIVE = 1;
    const HOST_ALIVE_SECONDS = 30;

    /**
     * @var \PDO
     */
    private $pdo;

    public function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function upsert(string $hostname)
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
     * @return array|null[]
     */
    public function getAvailableHostnames()
    {
        $query = sprintf(
            "SELECT (hostname) FROM %s WHERE status = %s AND ts_available >= %s;",
            self::TABLE_NAME,
            self::ACTIVE,
            time() - self::HOST_ALIVE_SECONDS
        );

        $stmt = $this->pdo->prepare($query);
        $stmt->execute();

        $hostsArray =  array_map(function ($data) {
            return isset($data['hostname']) ? $data['hostname'] : null;
        }, $stmt->fetchAll());

        return array_filter($hostsArray);
    }
}
