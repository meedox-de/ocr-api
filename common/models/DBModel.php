<?php

namespace common\models;

use common\lib\AbstractDatabaseProcessing;


class DBModel extends AbstractDatabaseProcessing
{
    /**
     * @return static
     */
    public static function execute() :self
    {
        return new self( self::class );
    }
}