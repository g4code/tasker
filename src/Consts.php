<?php

namespace G4\Tasker;

class Consts
{
    const STATUS_PENDING            = 0;
    const STATUS_MULTI_WORKING      = 5;
    const STATUS_WORKING            = 1;
    const STATUS_BROKEN             = 3;
    const STATUS_RETRY_FAILED       = 4;
    const STATUS_DONE               = 2;
    const STATUS_COMPLETED_NOT_DONE = 6;
    const STATUS_WAITING_FOR_RETRY  = 7;

    const PRIORITY_LOW             = 11;
    const PRIORITY_MEDIUM          = 41;
    const PRIORITY_HIGH            = 81;

    const PRIORITY_00              = 0;
    const PRIORITY_10              = 10;
    const PRIORITY_20              = 20;
    const PRIORITY_30              = 30;
    const PRIORITY_40              = 40;
    const PRIORITY_50              = 50;
    const PRIORITY_60              = 60;
    const PRIORITY_70              = 70;
    const PRIORITY_80              = 80;
    const PRIORITY_90              = 90;
    const PRIORITY_93              = 93;
    const PRIORITY_99              = 99;

    const RECURRING_TASK_STATUS_INACTIVE = 0;
    const RECURRING_TASK_STATUS_ACTIVE = 1;

    const LIMIT_DEFAULT            = 100;

    const STATUS_NAME_PENDING            = 'STATUS_PENDING';
    const STATUS_NAME_MULTI_WORKING      = 'STATUS_MULTI_WORKING';
    const STATUS_NAME_WORKING            = 'STATUS_WORKING';
    const STATUS_NAME_BROKEN             = 'STATUS_BROKEN';
    const STATUS_NAME_RETRY_FAILED       = 'STATUS_RETRY_FAILED';
    const STATUS_NAME_DONE               = 'STATUS_DONE';
    const STATUS_NAME_COMPLETED_NOT_DONE = 'STATUS_COMPLETED_NOT_DONE';
    const STATUS_NAME_WAITING_FOR_RETRY  = 'STATUS_WAITING_FOR_RETRY';

    const ORDER_BY_CREATED_ON      = 'ts_created';
    const ORDER_BY_PRIORITY        = 'priority';

    const ORDER_BY_NAME_DEFAULT    = self::ORDER_BY_NAME_CREATED_ON;
    const ORDER_BY_NAME_CREATED_ON = 'CREATED_ON';
    const ORDER_BY_NAME_PRIORITY   = 'PRIORITY';

    public static function getMap()
    {
        return array(
            self::STATUS_NAME_PENDING            => self::STATUS_PENDING,
            self::STATUS_NAME_MULTI_WORKING      => self::STATUS_MULTI_WORKING,
            self::STATUS_NAME_WORKING            => self::STATUS_WORKING,
            self::STATUS_NAME_BROKEN             => self::STATUS_BROKEN,
            self::STATUS_NAME_RETRY_FAILED       => self::STATUS_RETRY_FAILED,
            self::STATUS_NAME_DONE               => self::STATUS_DONE,
            self::STATUS_NAME_COMPLETED_NOT_DONE => self::STATUS_COMPLETED_NOT_DONE,
            self::STATUS_NAME_WAITING_FOR_RETRY  => self::STATUS_WAITING_FOR_RETRY,
            self::ORDER_BY_NAME_CREATED_ON       => self::ORDER_BY_CREATED_ON,
            self::ORDER_BY_NAME_PRIORITY         => self::ORDER_BY_PRIORITY,
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