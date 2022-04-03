<?php

namespace console\controller;

use common\lib\FunctionalHelper;
use common\models\ProcessedFilesModel;
use common\models\ProcessedPagesModel;
use common\models\QueueOcrModel;

class ApiDataExchange
{
    private string $token     = 'ynlom8q2ifntyb07';
    private int    $sourceApi = 0;
    private array  $response  = [
        'success'        => false,
        'errors'         => [],
        'processedFiles' => [],
    ];

    /**
     * error numbers:
     * 1 - security token wrong
     * 2 - sourceApi don't exist
     * 3 - data array don't exist
     * 4 - file array is empty
     * 5 - entry already exists
     * 6 - ocrEntry associated file don't exist
     * 7 - file can't move
     */
    public function __construct()
    {
        $token = FunctionalHelper::post( 'token' );

        // check security token
        if( $token !== $this->token )
        {
            $this->response['errors'][] = [
                'code' => 1,
            ];
            $this->sendResponse();
        }

        // choose request type
        if( isset( $_POST['queueOcrApiArray'] ) )
        {
            $this->receivingRequest();
        }
        elseif( isset( $_POST['deleteIds'] ) )
        {
            $this->deleteRequest( json_decode( $_POST['deleteIds'] ) );
        }
    }

    /**
     * saves all received documents for ocr queue
     *
     * @return void
     */
    private function receivingRequest() :void
    {
        $this->sourceApi = (int) FunctionalHelper::post( 'sourceApi' );

        // check sourceApi exists
        if( !in_array( $this->sourceApi, QueueOcrModel::SOURCE_API_IDS ) )
        {
            $this->response['errors'][] = [
                'code' => 2,
            ];
            $this->sendResponse();
        }

        // check data array exists
        if( !isset( $_POST['queueOcrApiArray'] ) )
        {
            $this->response['errors'][] = [
                'code' => 3,
            ];
            $this->sendResponse();
        }
        $data = json_decode( $_POST['queueOcrApiArray'] );

        // check files
        if( !empty( $data ) && empty( $_FILES ) )
        {
            $this->response['errors'][] = [
                'code' => 4,
            ];
            $this->sendResponse();
        }

        // save data
        if( $this->getQueueOcrEntries( $data ) )
        {
            $this->response['success'] = true;
        }

        $this->getProcessedEntries();

        $this->sendResponse();
    }

    /**
     * Deletes all files returned by the api
     *
     * @param array $deleteArray
     *
     * @return void
     */
    private function deleteRequest(array $deleteArray) :void
    {
        foreach( $deleteArray as $id )
        {
            ProcessedPagesModel::find()->fileId( $id )->delete();
            ProcessedFilesModel::find()->id( $id )->delete();
        }

        die();
    }


    /**
     * @return void
     */
    private function sendResponse() :void
    {
        header( 'Content-Type:application/json;charset=utf-8' );
        echo json_encode( $this->response );
        die();
    }

    /**
     * gets all entries for ocr queue with associated files
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

            // check file exists in $_FILES
            $arrayName = str_replace( '.', '_', $ocrEntry->file_name );
            if( !isset( $_FILES[$arrayName] ) )
            {
                $this->response['errors'][] = [
                    'code' => 6,
                    'id'   => $ocrEntry->id,
                ];
                continue;
            }

            $uploadedFile = $_FILES[$arrayName];

            // check temp directory exists or generate it
            $queueFilePath = DATA . 'queueOcrFiles';
            if( !is_dir( $queueFilePath ) )
            {
                mkdir( $queueFilePath, 0777, true );
            }

            // move file
            $queueFilePath .= DIRECTORY_SEPARATOR . $ocrEntry->file_name;
            if( !move_uploaded_file( $uploadedFile['tmp_name'], $queueFilePath ) )
            {
                $this->response['errors'][] = [
                    'code' => 7,
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

    /**
     * gets all ocr procesed files and associated pages
     *
     * @return void
     */
    private function getProcessedEntries() :void
    {
        foreach( ProcessedFilesModel::find()->all() as $page )
        {
            $this->response['processedFiles'][] = [
                'file'  => $page,
                'pages' => ProcessedPagesModel::find()->fileId( $page->id )->all(),
            ];
        }

        #TODO - übergebene Daten aus DB entfernen
    }
}