<?php

namespace common\models;

use common\lib\AbstractDatabaseProcessing;

class MigrationsModel extends AbstractDatabaseProcessing
{
    public const    TABLE_NAME = 'migrations';
    public const    COLUMNS    = [
        'name',
        'executed',
        'created_at',
        'updated_at',
    ];

    public const EXECUTED_UP        = 'up';
    public const EXECUTED_DOWN      = 'down';
    public const EXECUTED_FAILED    = 'failed';
    public const EXECUTED_TYPE_LIST = [
        self::EXECUTED_UP,
        self::EXECUTED_DOWN,
        self::EXECUTED_FAILED,
    ];

    /**
     * @return static
     */
    public static function find() :self
    {
        return new self( self::class );
    }


    /**
     * @param bool $name
     *
     * @return $this
     */
    public function name(string $name) :static
    {
        $this->whereValues['name'] = $name;
        return $this;
    }

}