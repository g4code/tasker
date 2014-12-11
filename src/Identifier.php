<?php
namespace G4\Tasker;

class Identifier
{

    const DELIMITER = '|';

    /**
     * @var array
     */
    private $hostnamePool;


    public function __construct($hostnamePool)
    {
        $this->hostnamePool = is_array($hostnamePool)
            ? $hostnamePool
            : explode(self::DELIMITER, $hostnamePool);
    }

    public function getOne()
    {
        return $this->hostnamePool[array_rand($this->hostnamePool)];
    }
}