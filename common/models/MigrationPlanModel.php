<?php

namespace common\models;

use common\lib\AbstractDatabaseProcessing;


class MigrationPlanModel extends AbstractDatabaseProcessing
{
    public const    TABLE_NAME = 'migration_plan';
    public const    COLUMNS    = [
        'id',
        'name',
        'active',
        'direction',
        'created_at',
        'updated_at',
    ];

    public const MIGRATION_DIRECTION_UP   = 'up';
    public const MIGRATION_DIRECTION_DOWN = 'down';


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

    /**
     * @param string $name
     *
     * @return $this
     */
    public function name(string $name) :static
    {
        $this->whereValues['name'] = $name;
        return $this;
    }

    /**
     * @param bool $active
     *
     * @return $this
     */
    public function active(bool $active) :static
    {
        $this->whereValues['active'] = $active;
        return $this;
    }
}