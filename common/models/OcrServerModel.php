<?php

namespace common\models;

use common\lib\AbstractDatabaseProcessing;


class OcrServerModel extends AbstractDatabaseProcessing
{
    public const    TABLE_NAME = 'ocr_server';
    public const    COLUMNS    = [
        'id',
        'name',
        'url',
        'port',
        'document_in_process_count',
        'document_total_count',
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