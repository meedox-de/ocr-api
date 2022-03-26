<?php

namespace console\controller;

use common\models\QueueOcrModel;
use DateTime;
use Imagick;


class CronjobOcrRequest
{
    private int   $numberOfRecognizingFiles = 1;
    private array $ocrDpi                   = [
        300,
        450,
        600,
    ];

    public function __construct()
    {
        $ocrQueue = $this->getOcrQueue();

        for( $key = 0; $key < $this->numberOfRecognizingFiles; $key++ )
        {
            $request = $ocrQueue[$key];
            $file = DATA . 'queueOcrFiles' . DIRECTORY_SEPARATOR . $request->file_name;

            $startTime = new DateTime();

            $this->startImagick($file);
        }
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


    /**
     * @throws \ImagickException
     */
    private function startImagick(string $file)
    {
        $imagick = new Imagick();
        $imagick->pingImage($file);
        $pages = $imagick->getNumberImages();
        $resolution = $imagick->getImageResolution();
    }
}