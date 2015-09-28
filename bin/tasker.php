<?php

namespace G4\Tasker;

// id long options is required
$options = getopt('', array('id:'));

$adapter = new \G4\DataMapper\Adapter\Mysql\Db($config['resources']['db']);

if(isset($options['id']) && $options['id'] > 0) {
    $runner = new Runner();
    $runner
        ->setTaskId($options['id'])
        ->execute();
} else {
    $taskMapper = new \G4\Tasker\Model\Mapper\Mysql\Task($adapter);
    $recuringMapper = new \G4\Tasker\Model\Mapper\Mysql\Task($adapter);

    // inject new tasks
    $injector = new Injector($taskMapper, $recuringMapper);
    $injector->run();

    // run task
    $cron = new Manager($taskMapper);
    $cron
        ->setRunner(__FILE__)
        ->run();
}
