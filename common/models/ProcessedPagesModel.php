<?php

namespace common\models;

use common\lib\AbstractDatabaseProcessing;


class ProcessedPagesModel extends AbstractDatabaseProcessing
{
    public const    TABLE_NAME = 'processed_pages';
    public const    COLUMNS    = [
        'id',
        'file_id',
        'page_number',
        'page_words',
        'page_text',
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

    /**
     * @param int $fileId
     *
     * @return $this
     */
    public function fileId(int $fileId) :static
    {
        $this->whereValues['fileId'] = $fileId;
        return $this;
    }
}