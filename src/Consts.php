<?php

namespace G4\Tasker;

class Consts
{
    const STATUS_PENDING           = 0;
    const STATUS_WORKING           = 1;
    const STATUS_BROKEN            = 3;
    const STATUS_RETRY_FAILED      = 4;
    const STATUS_DONE              = 2;

    const PRIORITY_LOW             = 11;
    const PRIORITY_MEDIUM          = 41;
    const PRIORITY_HIGH            = 81;

    const LIMIT_DEFAULT            = 100;

    const STATUS_NAME_PENDING      = 'STATUS_PENDING';
    const STATUS_NAME_WORKING      = 'STATUS_WORKING';
    const STATUS_NAME_BROKEN       = 'STATUS_BROKEN';
    const STATUS_NAME_RETRY_FAILED = 'STATUS_RETRY_FAILED';
    const STATUS_NAME_DONE         = 'STATUS_DONE';

    const ORDER_BY_CREATED_ON      = 'ts_created';
    const ORDER_BY_PRIORITY        = 'priority';

    const ORDER_BY_NAME_DEFAULT    = self::ORDER_BY_NAME_CREATED_ON;
    const ORDER_BY_NAME_CREATED_ON = 'CREATED_ON';
    const ORDER_BY_NAME_PRIORITY   = 'PRIORITY';

    public static function getMap()
    {
        return array(
            'STATUS_PENDING'      => self::STATUS_PENDING,
            'STATUS_WORKING'      => self::STATUS_WORKING,
            'STATUS_BROKEN'       => self::STATUS_BROKEN,
            'STATUS_RETRY_FAILED' => self::STATUS_RETRY_FAILED,
            'STATUS_DONE'         => self::STATUS_DONE,
            'CREATED_ON'          => self::ORDER_BY_CREATED_ON,
            'PRIORITY'            => self::ORDER_BY_PRIORITY,
        );
    }

    public static function getConst($constName)
    {
        $map = static::getMap();
        return static::isValid($constName)
            ? $map[$constName]
            : null;
    }

    public static function getName($const)
    {
        return array_search($const, static::getMap());
    }

    public static function getValidOrderBy()
    {
        return array(
            self::ORDER_BY_NAME_CREATED_ON,
            self::ORDER_BY_NAME_PRIORITY,
        );
    }

    public static function isValid($constName)
    {
        $map = static::getMap();
        return isset($map[$constName]);
    }
}