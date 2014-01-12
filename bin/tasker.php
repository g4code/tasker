<?php

namespace G4\Tasker;

// id long options is required
$options = getopt('', array('id:'));

if(isset($options['id']) && $options['id'] > 0) {
    $runner = new Runner();
    $runner
        ->setTaskId($options['id'])
        ->execute();
} else {

    // inject new tasks
    $injector = new Injector();
    $injector->run();

    // run task
    $cron = new Manager();
    $cron
        ->setRunner(__FILE__)
        ->run();
}
