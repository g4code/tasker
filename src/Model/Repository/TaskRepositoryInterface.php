<?php

interface TaskInterface
{
    public function getReservedTasks($limit);
    public function getOldMultiWorkingTasks();
}