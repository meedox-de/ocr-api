<?php

namespace console\controller;


use common\models\QueueOcrModel;

class CronjobOcrRequest
{
    private int $numberOfRecognizingFiles = 1;

    public function __construct()
    {
        $ocrQueue = $this->getOcrQueue();
        var_dump( $ocrQueue );
    }

    /**
     * queries the ocr-requests from queue. API-ID 1 is preferred
     *
     * @return array
     */
    private function getOcrQueue() :array
    {
        $query = QueueOcrModel::find();
        $query->orderBy( [
                             'source_api' => SORT_ASC,
                             'created_at' => SORT_ASC,
                         ] );
        return $query->all();
    }


    private function getImage()
    {

    }
}