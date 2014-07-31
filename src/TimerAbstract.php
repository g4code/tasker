<?php
namespace G4\Tasker;

class TimerAbstract
{
    private $timerStart;

    private $timerStop;

    protected function timerStart()
    {
        $this->timerStart = microtime(true);
        return $this;
    }

    protected function timerStop()
    {
        $this->timerStop = microtime(true);
        return $this;
    }

    protected function getTimerStart()
    {
        return $this->timerStart;
    }

    protected function getTimerStop()
    {
        return $this->timerStop;
    }

    protected function getTotalTime()
    {
        return $this->timerStop - $this->timerStart;
    }

    protected function getRunningTime()
    {
        return microtime(true) - $this->timerStart;
    }
}
