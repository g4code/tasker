<?php
namespace G4\Tasker;

class TimerAbstract
{
    private $_timerStart;

    private $_timerStop;

    protected function _timerStart()
    {
        $this->_timerStart = microtime(true);
        return $this;
    }

    protected function _timerStop()
    {
        $this->_timerStop = microtime(true);
        return $this;
    }

    protected function _getTimerStart()
    {
        return $this->_timerStart;
    }

    protected function _getTimerStop()
    {
        return $this->_timerStop;
    }

    protected function _getTotalTime()
    {
        return $this->_timerStop - $this->_timerStart;
    }

}
