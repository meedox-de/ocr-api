<?php

namespace console\controller;

use common\lib\DebugHelper;
use common\models\QueueOcrModel;
use DateTime;
use Exception;
use Vaites\ApacheTika\Client;


class TikaController
{
    private int   $numberOfRecognizingFiles = 1;
    private array $documentResult;

    //private string $url = 'https://nas03:9998';
    private string $url  = 'https://throemer.meedox.de:9998';


    /**
     * @throws Exception
     */
    public function __construct()
    {
        $ocrQueue = $this->getOcrQueue();
        if( empty( $ocrQueue ) )
        {
            return;
        }
        DebugHelper::pre( $ocrQueue );

        // deactivate time limit
        set_time_limit( 0 );

        for( $key = 0; $key < $this->numberOfRecognizingFiles; $key++ )
        {
            // collect data
            $this->documentResult               = [];
            $request                            = $ocrQueue[$key];
            $file                               = DATA . 'queueOcrFiles' . DIRECTORY_SEPARATOR . $request->file_name . '.' . QueueOcrModel::EXTENSION_PDF;
            $startTime                          = new DateTime();
            $this->documentResult['file_words'] = 0;
            $this->documentResult['pages']      = 1;

            // mark entry as in_progress
            $updateQuery = QueueOcrModel::find();
            $updateQuery->id( $request->id );
            $updateQuery->update( [
                                      'updateColumns' => [
                                          'in_process' => 1,
                                      ],
                                  ] );

            if( !$this->startTikaRequest( $request->id, $file) )
            {
                continue;
            }
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
        $query->andWhere( 'queue_ocr.runs < 2' );
        $query->inProcess( false );
        $query->orderBy( [
                             'source_api' => SORT_ASC,
                             'created_at' => SORT_ASC,
                         ] );
        return $query->all();
    }

    /**
     * send request to apache Tika server
     *
     * @param int    $id
     * @param string $filePath
     *
     * @return bool
     * @throws Exception
     */
    private function startTikaRequest(int $id, string $file) :bool
    {
        $client = Client::make( $this->url );
        $client->setEncoding( 'UTF-8' );
        $client->setTimeout( 0 );
$client->setOCRLanguage( 'deu' );
//$client->setOCRLanguages(['deu', 'eng']);

        $metadata = $client->getMetadata( $file );
        $ocrResult = $client->getHTML( $file );

        DebugHelper::pre($metadata);
        var_dump( $client->getHTML( $file ) );

        return true;
    }

}