<?php

namespace common\models;

use common\lib\AbstractDatabaseProcessing;


class QueueOcrModel extends AbstractDatabaseProcessing
{
    public const    TABLE_NAME = 'queue_ocr';
    public const    COLUMNS    = [
        'id',
        'source_api',
        'source_id',
        'file_name',
        'file_extension',
        'runs',
        'error_message',
        'created_at',
        'updated_at',
    ];

    public const SOURCE_API_MEEDOX      = 1;
    public const SOURCE_API_PRIVATE     = 2;
    public const SOURCE_API_PRIVATE_DEV = 3;

    public const SOURCE_API_IDS = [
        self::SOURCE_API_MEEDOX,
        self::SOURCE_API_PRIVATE,
        self::SOURCE_API_PRIVATE_DEV,
    ];

    public const EXTENSION_PDF = 'pdf';


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
     * @param int $sourceApi
     *
     * @return $this
     */
    public function sourceApi(int $sourceApi) :static
    {
        $this->whereValues['source_api'] = $sourceApi;
        return $this;
    }

    /**
     * @param int $sourceId
     *
     * @return $this
     */
    public function sourceId(int $sourceId) :static
    {
        $this->whereValues['source_id'] = $sourceId;
        return $this;
    }

    /**
     * @param string $fileName
     *
     * @return $this
     */
    public function fileName(string $fileName) :static
    {
        $this->whereValues['file_name'] = $fileName;
        return $this;
    }
}