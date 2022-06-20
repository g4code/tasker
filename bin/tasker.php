<?php

namespace G4\Tasker;

use G4\Tasker\Model\Repository\Mysql\ErrorRepository;
use G4\Tasker\Model\Repository\Mysql\RecurringRepository;
use G4\Tasker\Model\Repository\Mysql\TaskRepository;

// id long options are required
$options = getopt('', array('id:'));

$dbParams = $config['resources']['db']['params'];
$dsn = sprintf('mysql:dbname=%s;host=%s', $dbParams['dbname'], $dbParams['host']);
$pdo = new \PDO($dsn, $dbParams['username'], $dbParams['password']);
if (isset($config['resources']['db']['params']['charset'])) {
    $this->pdo->exec('SET NAMES ' . $config['resources']['db']['params']['charset']);
}

$taskRepository = new TaskRepository($pdo);
$errorRepository = new ErrorRepository($pdo);
$recurringRepository = new RecurringRepository($pdo);

if(isset($options['id']) && $options['id'] > 0) {
    $runner = new Runner($taskRepository, $errorRepository);
    $runner
        ->setTaskId($options['id'])
        ->execute();
} else {
    // inject new tasks
    $injector = new Injector($taskRepository, $recurringRepository);
    $injector->run();

    // run task
    $cron = new Manager($taskRepository);
    $cron
        ->setRunner(__FILE__)
        ->run();
}
