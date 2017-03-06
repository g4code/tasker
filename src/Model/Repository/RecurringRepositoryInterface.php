<?php

namespace G4\Tasker\Model\Repository;


use G4\Tasker\Model\Domain\Recurring;

interface RecurringRepositoryInterface
{
    /**
     * @return array|Recurring[]
     */
    public function getNextTasks();
}