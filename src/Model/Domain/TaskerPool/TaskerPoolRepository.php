<?php

namespace G4\Tasker\Model\Domain\TaskerPool;

class TaskerPoolRepository
{
    const TABLE_NAME = 'tasker_pool';

    /**
     * @var \PDO
     */
    private $pdo;

    public function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function upsert(string $taskerPoolEntity)
    {
        $query = sprintf(
            "INSERT INTO " . self::TABLE_NAME . "(`hostname`,`status`,`ts_available`) VALUES ('%s',%s,%s) ON DUPLICATE KEY UPDATE ts_available=UNIX_TIMESTAMP();",
            $taskerPoolEntity,
            1,
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
        $query = "SELECT (hostname) FROM " . self::TABLE_NAME . " WHERE status = 1 AND ts_available >= UNIX_TIMESTAMP() - 30;";
        $stmt = $this->pdo->prepare($query);
        $stmt->execute();

        return array_map(function ($data) {
            return isset($data['hostname']) ? $data['hostname'] : null;
        }, $stmt->fetchAll());
    }
}
