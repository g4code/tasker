<?php
namespace G4\Tasker;

class Forker
{
    private $_context = 'php';

    private $_runner;

    private $_options;

    public function fork()
    {
        $pid = pcntl_fork();

        if ($pid == -1) {
            throw new \Exception('could not fork');
        } else if ($pid) {
            // parent process
        } else {
            $cmd = sprintf('%s %s %s', $this->_context, $this->_runner, $this->_formatOptions());
            echo shell_exec($cmd), PHP_EOL;
            exit(0);
        }
    }

    private function _formatOptions()
    {
        $options = $this->getOptions();

        if(empty($options) || !is_array($options)) {
            return '';
        }

        foreach($options as $key => $value) {
            $dashes = strlen($key) == 1 ? '-' : '--';
            $segments[] = "{$dashes}{$key} {$value}";
        }

        return implode(' ', $segments);
    }

    public function getRunner()
    {
        if(null === $this->_runner) {
            throw new \Exception('Runner is not set');
        }
        return $this->_runner;
    }

    public function setRunner($value)
    {
        $this->_runner = $value;
        return $this;
    }

    public function getOptions()
    {
        return $this->_options;
    }

    public function setOptions(array $value)
    {
        $this->_options = $value;
        return $this;
    }

}