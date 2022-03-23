<?php

namespace console\controller;

use common\lib\FunctionalHelper;
use common\models\QueueOcrModel;

class OcrRequest
{
    private string $token     = 'ynlom8q2ifntyb07';
    private int    $sourceApi = 0;
    private array  $response  = [
        'errors'  => [],
        'success' => false,
    ];

    /**
     * error numbers:
     * 1 - security token wrong
     * 2 - sourceApi dont exists
     * 3 - data array dont exists
     * 4 - file array dont exists
     * 5 - entry already exists
     */
    public function __construct()
    {
        header('Content-Type: application/json');

        $token           = FunctionalHelper::post( 'token' );
        $this->sourceApi = (int) FunctionalHelper::post( 'sourceApi' );

        // check security token
        if( $token !== $this->token )
        {
            $this->response['errors'][] = [
                'code' => 1,
            ];
            return false;
        }

        // check sourceApi exists
        if( !in_array( $this->sourceApi, QueueOcrModel::SOURCE_API_IDS ) )
        {
            $this->response['errors'][] = [
                'code' => 2,
            ];
            return false;
        }

        // check data array exists
        if( !isset( $_POST['queueOcrApiArray'] ) )
        {
            $this->response['errors'][] = [
                'code' => 3,
            ];
            return false;
        }
        $data = json_decode( $_POST['queueOcrApiArray'] );

        // check files
        if( !isset( $_POST['files'] ) )
        {
            $this->response['errors'][] = [
                'code' => 4,
            ];
            return false;
        }
var_dump($_FILES);
        die();
        //move_uploaded_file(json_decode($_POST['files']['path']), CONSOLE . 'public' . DIRECTORY_SEPARATOR . 'cb0cfe7c570530d4.pdf' );

        if( $this->getQueueOcrEntries( $data ) )
        {
            $this->response['success'] = true;
        }

        header( 'Content-Type:application/json;charset=utf-8' );
        echo json_encode( $this->response );
    }

    /**
     * gets all entries for ocr queue
     *
     * @param array $data
     *
     * @return bool
     */
    private function getQueueOcrEntries(array $data) :bool
    {
        foreach( $data as $ocrEntry )
        {
            // check duplicate entries
            if( QueueOcrModel::find()->sourceApi( $this->sourceApi )->sourceId( $ocrEntry->id )->fileName( $ocrEntry->file_name )->exists() )
            {
                $this->response['errors'][] = [
                    'code' => 5,
                    'id'   => $ocrEntry->id,
                ];
                continue;
            }

            QueueOcrModel::find()->insert( [
                                               'insertColumns' => [
                                                   'source_api' => $this->sourceApi,
                                                   'source_id'  => $ocrEntry->id,
                                                   'file_name'  => $ocrEntry->file_name,
                                               ],
                                           ] );
        }
        return true;
    }
}