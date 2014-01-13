<?php
namespace G4\Tasker;

abstract class TaskAbstract
{
    private $_data;

    public function getData()
    {
        return $this->_data;
    }

    public function setData($value)
    {
        $this->_data = json_decode($value, true);
        return $this;
    }

    abstract public function execute();
}