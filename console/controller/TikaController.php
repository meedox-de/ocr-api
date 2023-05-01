<?php

namespace console\controller;

use common\lib\FunctionalHelper;
use common\models\OcrServerModel;
use common\models\PingServerLogModel;
use common\models\ProcessedFilesModel;
use common\models\ProcessedPagesModel;
use common\models\QueueOcrModel;
use DateTime;
use Exception;
use Vaites\ApacheTika\Client;


class TikaController
{
    private int $numberOfRecognizingFiles = 1;
    private int $fileOcrTimeout           = 45;
    private int $maxRunsPerFile           = 2;
    private int $maxDocumentsPerServer    = 2;


    private string $protocol   = 'https';
    private array  $serverList = [];


    /**
     * @throws Exception
     */
    public function __construct()
    {
        // check files are in_process since more than ... minutes
        $this->checkFileOcrTimeout();

        // load queue file list
        $ocrQueue = $this->getOcrQueue();

        // deactivate time limit
        set_time_limit( 0 );


        // start process files
        foreach( $ocrQueue as $entry )
        {
            $serverId = $this->getServerIdFromLoadBalancer();
            if( is_null( $serverId ) )
            {
                break;
            }

            // collect data
            $file = DATA . 'queueOcrFiles' . DIRECTORY_SEPARATOR . $entry->file_name . '.' . QueueOcrModel::EXTENSION_PDF;

            $startTime = new DateTime();

            // mark entry as in_progress
            $updateQuery = QueueOcrModel::find();
            $updateQuery->id( $entry->id );
            $updateQuery->update( [
                                      'updateColumns' => [
                                          'in_process' => (new DateTime())->format( 'Y-m-d H:i:s' ),
                                          'server_id'  => $serverId,
                                      ],
                                  ] );


            // start recognition
            $resultArray         = $this->startTikaRequest( $serverId, $file );
            $resultArray['time'] = $startTime->diff( new DateTime() );

            // log failed request
            if( isset( $resultArray['error'] ) )
            {
                $updateColumns = [
                    'in_process'    => null,
                    'server_id'     => null,
                    'error_message' => $resultArray['error'],
                ];

                if( $resultArray['error'] !== 'Empty reply from server' )
                {
                    $updateColumns['runs'] = $entry->runs + 1;
                }

                $query = QueueOcrModel::find();
                $query->id( $entry->id );
                $query->update( [
                                    'updateColumns' => $updateColumns,
                                ] );
                continue;
            }

            // save result
            if( !$this->saveOcrRecognizedData( $entry, $resultArray ) )
            {
                continue;
            }

            // delete queue entry and file
            if( !$this->deleteQueueData( $entry->id, $file ) )
            {
                continue;
            }
        }
    }


    /**
     * Function to check if a file is longer than ... minutes in process
     *
     * @return void
     */
    private function checkFileOcrTimeout() :void
    {
        $query = QueueOcrModel::find();
        $query->andWhere( 'queue_ocr.in_process IS NOT NULL' );
        $query->andWhere( 'queue_ocr.server_id IS NOT NULL' );
        $query->andWhere( 'queue_ocr.in_process < DATE_SUB(NOW(), INTERVAL :minutes MINUTE)', ['minutes' => $this->fileOcrTimeout] );
        $query->update( [
                            'updateColumns' => [
                                'in_process' => null,
                                'server_id'  => null,
                            ],
                        ] );
    }

    /**
     * queries the ocr-requests from queue. API-ID 1 is preferred
     *
     * @return array
     */
    private function getOcrQueue() :array
    {
        $query = QueueOcrModel::find();
        $query->andWhere( 'queue_ocr.runs < ' . $this->maxRunsPerFile );
        $query->inProcess( false );
        $query->orderBy( [
                             'source_api' => SORT_ASC,
                             'created_at' => SORT_ASC,
                         ] );
        $query->limit( $this->numberOfRecognizingFiles );
        $ocrQueue = $query->all();

        if( empty( $ocrQueue ) )
        {
            die( "no files in queue" );
        }

        return $ocrQueue;
    }

    /**
     * @return int|null
     */
    private function getServerIdFromLoadBalancer() :?int
    {
        // load server list
        $this->getServerList();

        // ping tika server
        $pingResult = [];
        foreach( $this->serverList as $server )
        {
            $ping = FunctionalHelper::pingUrl( $server->url, $server->port );

            // save ping to ping_server_log table
            PingServerLogModel::find()->insert( [
                                                    'insertColumns' => [
                                                        'server_id' => $server->id,
                                                        'ping'      => $ping,
                                                    ],
                                                ] );
            // save ping in array if not null
            if( !is_null( $ping ) )
            {
                $pingResult[$server->id] = $ping;
            }
        }
        // sort by ping time
        asort( $pingResult );

        // get the first entry from $pingResult
        return array_key_first( $pingResult );
    }

    /**
     * Function to get the url from a server
     *
     * @param int $serverId
     *
     * @return string
     */
    private function getServerUrl(int $serverId) :string
    {
        foreach( $this->serverList as $server )
        {
            if( $server->id === $serverId )
            {
                return $this->protocol . '://' . $server->url . ':' . $server->port;
            }
        }

        return '';
    }

    /**
     * send request to apache Tika server
     *
     * @param int    $serverId
     * @param string $file
     *
     * @return array
     */
    private function startTikaRequest(int $serverId, string $file) :array
    {
        $result = [];
        try
        {
            // set server document count +1
            $query = OcrServerModel::find();
            $query->id( $serverId );
            $query->update( [
                                'updateSpecials' => [
                                    'document_in_process_count' => 'document_in_process_count + 1',
                                    'document_total_count'      => 'document_total_count + 1',
                                ],
                            ] );


            $client = Client::make( $this->getServerUrl( $serverId ) );
            $client->setEncoding( 'UTF-8' );
            $client->setTimeout( 0 );
            $client->setHeader( 'X-Tika-PDFOcrStrategy', 'ocr_only' );
            $client->setOCRLanguages( [
                                          'eng',
                                          'deu',
                                      ] );

            $result['text']  = $client->getHTML( $file );
            $result['pages'] = $client->getMetadata( $file )->pages;
        }
        catch( Exception $e )
        {
            $result['error'] = $e->getMessage();
        }

        // set server document count -1
        $query = OcrServerModel::find();
        $query->id( $serverId );
        $query->update( [
                            'updateSpecials' => [
                                'document_in_process_count' => 'document_in_process_count - 1',
                            ],
                        ] );

        return $result;
    }

    /**
     * Function to load all servers with less than maxDocumentsPerServer documents in process
     *
     * @return array
     */
    private function getServerList() :void
    {
        $query = OcrServerModel::find();
        $query->andWhere( OcrServerModel::TABLE_NAME . '.document_in_process_count < :maxCount', ['maxCount' => $this->maxDocumentsPerServer] );
        $this->serverList = $query->all();

        if( empty( $this->serverList ) )
        {
            die( "no servers available" );
        }
    }

    /**
     * @param object $request
     * @param array  $result
     *
     * @return bool
     */
    private function saveOcrRecognizedData(object $request, array $result) :bool
    {
        // cut <html> and <head> from text (with <body> tag)
        $replacedText = preg_replace( '/<html.*<body>/s', '', $result['text'] );

        // cut </body> and </html> from text
        $replacedText = str_replace( '</body>', '', $replacedText );
        $replacedText = str_replace( '</html>', '', $replacedText );

        // trim text
        $replacedText = trim( $replacedText );

        // save document result
        $insertId = ProcessedFilesModel::find()->insert( [
                                                             'insertColumns' => [
                                                                 'source_api' => $request->source_api,
                                                                 'source_id'  => $request->source_id,
                                                                 'file_name'  => $request->file_name,
                                                                 'file_pages' => $result['pages'],
                                                                 'file_words' => str_word_count( $replacedText ),
                                                                 'ocr_time'   => $result['time']->i . ':' . $result['time']->s,
                                                             ],
                                                         ] );


        // check db entry
        if( !$insertId )
        {
            QueueOcrModel::find()->id( $request->id )->update( [
                                                                   'updateColumns' => [
                                                                       'in_process'    => 0,
                                                                       'runs'          => $request->runs + 1,
                                                                       'error_message' => 'OCR Result was not saved in "processed_files" table',
                                                                   ],
                                                               ] );
            return false;
        }


        // save all recognized pages
        // pages are separated by <div class="page">
        $replacedText = explode( '<div class="page">', $replacedText );

        // save every page
        $page = 1;
        foreach( $replacedText as $rawPageText )
        {
            $rawPageText = str_replace( '<div class="ocr">', '', $rawPageText );
            $rawPageText = str_replace( '<div class="ocr"/>', '', $rawPageText );
            $rawPageText = str_replace( '<div class="ocr" />', '', $rawPageText );
            $pageText    = str_replace( '</div>', '', $rawPageText );
            $pageText    = trim( $pageText );

            if( empty( $pageText ) )
            {
                continue;
            }

            $pageInsertId = ProcessedPagesModel::find()->insert( [
                                                                     'insertColumns' => [
                                                                         'file_id'     => (int) $insertId,
                                                                         'page_number' => $page,
                                                                         'page_words'  => str_word_count( $pageText ),
                                                                         'page_text'   => $pageText,
                                                                     ],
                                                                 ] );
            $page++;

            // check db entry
            if( !$pageInsertId )
            {
                QueueOcrModel::find()->id( $request->id )->update( [
                                                                       'updateColumns' => [
                                                                           'in_process'    => 0,
                                                                           'runs'          => $request->runs + 1,
                                                                           'error_message' => 'OCR page result was not saved in "processed_pages" table',
                                                                       ],
                                                                   ] );
                return false;
            }
        }

        return true;
    }

    /**
     * @param int    $id
     * @param string $file
     *
     * @return bool
     */
    private function deleteQueueData(int $id, string $file) :bool
    {
        // delete file
        if( !unlink( $file ) )
        {
            QueueOcrModel::find()->id( $id )->update( [
                                                          'updateColumns'  => [
                                                              'in_process'    => 0,
                                                              'error_message' => 'file was not deleted',
                                                          ],
                                                          'updateSpecials' => [
                                                              'runs' => 'runs + 1',
                                                          ],
                                                      ] );
            return false;
        }

        // delete queue_ocr entry
        if( !QueueOcrModel::find()->id( $id )->delete() )
        {
            QueueOcrModel::find()->id( $id )->update( [
                                                          'updateColumns'  => [
                                                              'in_process'    => 0,
                                                              'error_message' => 'queue_ocr entry was not deleted',
                                                          ],
                                                          'updateSpecials' => [
                                                              'runs' => 'runs + 1',
                                                          ],
                                                      ] );
            return false;
        }

        return true;
    }
}