<?php
namespace G4\Tasker;

class Forker
{
    private $context = 'php';

    private $runner;

    private $options;

    public function fork()
    {
        $pid = pcntl_fork();

        if ($pid === -1) {
            throw new \RuntimeException('could not fork');
        } elseif ($pid) {
            // parent process
        } else {
            $cmd = sprintf('%s %s %s', $this->context, $this->runner, $this->formatOptions());
            echo shell_exec($cmd), PHP_EOL;
            exit(0);
        }
    }

    private function formatOptions()
    {
        $options = $this->getOptions();

        if(empty($options) || !is_array($options)) {
            return '';
        }

        foreach($options as $key => $value) {
            $dashes = strlen($key) === 1 ? '-' : '--';
            $segments[] = "{$dashes}{$key} {$value}";
        }

        return implode(' ', $segments);
    }

    public function getRunner()
    {
        if(null === $this->runner) {
            throw new \Exception('Runner is not set');
        }
        return $this->runner;
    }

    public function setRunner($value)
    {
        $this->runner = $value;
        return $this;
    }

    public function getOptions()
    {
        return $this->options;
    }

    public function setOptions(array $value)
    {
        $this->options = $value;
        return $this;
    }

}