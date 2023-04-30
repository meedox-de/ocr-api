<?php

namespace common\models;

use common\lib\AbstractDatabaseProcessing;


class PingServerLogModel extends AbstractDatabaseProcessing
{
    public const    TABLE_NAME = 'ping_server_log';
    public const    COLUMNS    = [
        'id',
        'server_id',
        'ping',
        'created_at',
        'updated_at',
    ];


    /**
     * @return static
     */
    public static function find() :self
    {
        return new self( self::class );
    }

    /**
     * @param int $id
     *
     * @return $this
     */
    public function id(int $id) :static
    {
        $this->whereValues['id'] = $id;
        return $this;
    }

}