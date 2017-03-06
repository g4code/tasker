<?php
namespace G4\Tasker;

class ZombieKiller
{
    const PROCESS_NAME = 'tasker.php';

    /**
     *
     * @var int
     */
    private $maxExecutionTime;

    /**
     *
     * @var array
     */
    private $processes;

    public function __construct()
    {
        $this->processes = array();
    }

    public function kill()
    {
        if (!$this->maxExecutionTime) {
            throw new \InvalidArgumentException('Max execution time is not set.');
        }

        $this
            ->fetchZombieProcesses()
            ->killZombieProcesses();
    }

    public function setMaxExecutionTime($value)
    {
        $this->maxExecutionTime = $value;
        return $this;
    }

    private function fetchZombieProcesses()
    {
        exec('ps -eo pid,etime,args | grep -v grep | grep ' . self::PROCESS_NAME, $response);

        // form array of pid and execution time
        preg_match_all('~(\d+)\s+([^\s]+)\s[^\d]+.*~', implode("\n", $response), $response);

        foreach ($response[2] as $key => $time) {

            // parse POSIX execution time to seconds
            $seconds = 0;
            $datetime = explode('-', $time);
            if (count($datetime) > 1) {
                $seconds += $datetime[0] * 24 * 3600;
            }
            $datetime = array_reverse(explode(':', $datetime[count($datetime) - 1]));
            $seconds += (isset($datetime[2]) ? $datetime[2] * 3600 : 0) + $datetime[1] * 60 + $datetime[0];

            // add to array pids of processes with execution time bigger than specified value
            if ($seconds > $this->maxExecutionTime) {
                $this->processes[] = $response[1][$key];
            }
        }
        return $this;
    }

    private function killZombieProcesses()
    {
        foreach ($this->processes as $pid) {
            exec('kill -9 ' . $pid);
        }
        return $this;
    }
}