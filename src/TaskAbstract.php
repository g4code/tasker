<?php
namespace G4\Tasker;

abstract class TaskAbstract
{
    private $createdTs;

    private $data;

    private $meta;

    private $priority;

    public function addDelay($value)
    {
        $this->createdTs = $this->getTsCreated() + $value;
        return $this;
    }

    public function getData()
    {
        return $this->data;
    }

    public function getTsCreated()
    {
        return empty($this->createdTs)
            ? time()
            : $this->createdTs;
    }

    public function getEncodedData()
    {
        return $this->data !== null
            ? json_encode($this->data, JSON_UNESCAPED_UNICODE)
            : '';
    }

    public function getName()
    {
        return str_replace("\\", '\\\\', get_class($this));
    }

    public function getPriority()
    {
        return $this->priority !== null
            ? $this->priority
            : Consts::PRIORITY_MEDIUM;
    }

    public function setData(array $value)
    {
        $this->verifyData($value);
        $this->data = $value;
        return $this;
    }

    public function setEncodedData($value)
    {
        $this->data = json_decode($value, true);
        return $this;
    }

    public function setPriority($value)
    {
        $this->priority = $value;
        return $this;
    }

    abstract public function execute();

    protected function addMeta($key, $required = false, $valid = null, $default = null)
    {
        if(!is_string($key) || empty($key)) {
            throw new \Exception('Meta key must be non empty string');
        }

        if(isset($this->meta[$key])) {
            throw new \Exception('Meta key already declared');
        }

        $this->meta[$key] = array(
            'required' => (bool) $required,
            'valid'    => $valid,
            'default'  => $default,
        );

        return $this;
    }

    protected function verifyData($data)
    {
        if(empty($data)) {
            throw new \Exception('If data is set, it must be non empty array');
        }

        // if meta is not set, or is set to empty array, return true since we don't have anything to verify
        if(null === $this->meta || (is_array($this->meta) && empty($this->meta))) {
            return true;
        }

        foreach ($this->meta as $key => $value) {
            if($value['required']) {
                if(!isset($data[$key]) || empty($data[$key])) {
                    $class = get_called_class();
                    throw new \Exception("Task '{$class}' requires '{$key}' to be set");
                }
            }
        }

        return true;
    }
}