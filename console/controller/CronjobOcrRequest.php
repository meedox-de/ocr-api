<?php

namespace console\controller;

use common\models\ProcessedFilesModel;
use common\models\ProcessedPagesModel;
use common\models\QueueOcrModel;
use DateInterval;
use DateTime;
use Imagick;
use ImagickException;
use thiagoalessio\TesseractOCR\TesseractOCR;
use thiagoalessio\TesseractOCR\TesseractOcrException;
use thiagoalessio\TesseractOCR\UnsuccessfulCommandException;


class CronjobOcrRequest
{
    private int   $numberOfRecognizingFiles = 1;
    private int   $ocrDpi                   = 600;
    private array $documentResult;
    private array $notResultArray           = [
        'pages',
        'resolution',
        'file_words',
    ];

    private const MIME_TYPES_PDF        = 'application/pdf';
    private const MIME_TYPES_IMAGE_HEIC = 'image/heic';
    private const MIME_TYPES_IMAGE_JPEG = 'image/jpeg';
    private const MIME_TYPES_IMAGE_GIF  = 'image/gif';
    private const MIME_TYPES_IMAGE_PNG  = 'image/png';

    private const EXTENSIONS_FOR_IMAGICK = [
        self::MIME_TYPES_PDF,
        self::MIME_TYPES_IMAGE_HEIC,
    ];


    /**
     * @throws ImagickException
     */
    public function __construct()
    {
        $ocrQueue = $this->getOcrQueue();

        // deactivate time limit because there is no limit for the number of files being uploaded
        set_time_limit( 0 );

        for( $key = 0; $key < $this->numberOfRecognizingFiles; $key++ )
        {
            $this->documentResult = [];
            $request              = $ocrQueue[$key];
            $file                 = DATA . 'queueOcrFiles' . DIRECTORY_SEPARATOR . $request->file_name;

            $startTime = new DateTime();

            if( in_array( $request->file_extension, self::EXTENSIONS_FOR_IMAGICK ) )
            {
                if( !$this->startImagick( $request->id, $file ) )
                {
                    continue;
                }
            }
            else
            {
                if( !$this->startTesseract( $request->id, $file ) )
                {
                    continue;
                }
            }

            $endTime = new DateTime();
            $time    = $startTime->diff( $endTime );

            if( !$this->saveOcrRecognizedData( $request, $time ) )
            {
                continue;
            }

            if( !$this->deleteQueueData( $request->id, $file ) )
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
        $query->orderBy( [
                             'source_api' => SORT_ASC,
                             'created_at' => SORT_ASC,
                         ] );
        return $query->all();
    }


    /**
     * creates from every page a png and start tesseract
     *
     * @param int    $id
     * @param string $file
     *
     * @return bool
     * @throws ImagickException
     * @throws TesseractOcrException
     * @throws UnsuccessfulCommandException
     */
    private function startImagick(int $id, string $file) :bool
    {
        $imagick = new Imagick();
        $imagick->pingImage( $file );
        $pages                              = $imagick->getNumberImages();
        $this->documentResult['pages']      = $pages;
        $this->documentResult['resolution'] = $imagick->getImageResolution()['x'];
        $this->documentResult['file_words'] = 0;

        $imagick->setResolution( $this->ocrDpi, $this->ocrDpi );
        $imagick->setImageFormat( 'png' );

        // process all pdf sites
        for( $currentPage = 0; $currentPage < $pages; $currentPage++ )
        {
            // read specific page
            $imagick->readImage( $file . '[' . $currentPage . ']' );

            // check temp directory exists or generate it
            $tempPath = DATA . 'temp';
            if( !is_dir( $tempPath ) )
            {
                mkdir( $tempPath, 0777, true );
            }

            $tempFile = $tempPath . DIRECTORY_SEPARATOR . 'temp.png';

            // create png from pdf page
            $imagick->writeImage( $tempFile );

            // error
            if( !is_file( $tempFile ) )
            {
                QueueOcrModel::find()->id( $id )->update( [
                                                              'updateColumns'  => [
                                                                  'error_message' => 'imagick error: Die Datei wurde nicht gefunden - "' . $tempFile . '"',
                                                              ],
                                                              'updateSpecials' => [
                                                                  'runs' => 'runs+1',
                                                              ],
                                                          ] );
                return false;
            }

            if( !$this->startTesseract( $id, $tempFile, $currentPage ) )
            {
                return false;
            }

            // delete temporary png file
            unlink( $tempFile );
        }

        return true;
    }


    /**
     * @param string $tempFile
     * @param int    $currentPage
     *
     * @return bool
     * @throws TesseractOcrException
     * @throws UnsuccessfulCommandException
     */
    private function startTesseract(int $id, string $tempFile, int $currentPage = 0) :bool
    {
        $tesseract = new TesseractOCR();
        $tesseract->lang( 'deu', 'eng' );
        $tesseract->image( $tempFile );

        try
        {
            $this->documentResult[$currentPage] = $tesseract->run();
        }
        catch( TesseractOcrException $e )
        {
            QueueOcrModel::find()->id( $id )->update( [
                                                          'updateColumns'  => [
                                                              'error_message' => 'tesseract error: ' . $e,
                                                          ],
                                                          'updateSpecials' => [
                                                              'runs' => 'runs+1',
                                                          ],
                                                      ] );
            return false;
        }
        $this->documentResult['file_words'] = $this->documentResult['file_words'] + str_word_count( $this->documentResult[$currentPage] );;

        return true;
    }


    /**
     * @param object       $request
     * @param DateInterval $time
     *
     * @return bool
     */
    private function saveOcrRecognizedData(object $request, DateInterval $time) :bool
    {
        // save document result
        $insertId = ProcessedFilesModel::find()->insert( [
                                                             'insertColumns' => [
                                                                 'source_api' => $request->source_api,
                                                                 'source_id'  => $request->source_id,
                                                                 'file_name'  => $request->file_name,
                                                                 'file_pages' => $this->documentResult['pages'],
                                                                 'file_words' => $this->documentResult['file_words'],
                                                                 'ocr_time'   => $time->i . ':' . $time->s,
                                                             ],
                                                         ] );

        // check db entry
        if( !$insertId )
        {
            QueueOcrModel::find()->id( $request->id )->update( [
                                                                   'updateColumns'  => [
                                                                       'error_message' => 'OCR Result was not saved in "processed_files" table',
                                                                   ],
                                                                   'updateSpecials' => [
                                                                       'runs' => 'runs+1',
                                                                   ],
                                                               ] );
            return false;
        }

        // save all recognized pages
        foreach( $this->documentResult as $key => $result )
        {
            // ignore no page keys
            if( in_array( $key, $this->notResultArray ) )
            {
                continue;
            }

            $pageInsertId = ProcessedPagesModel::find()->insert( [
                                                                     'insertColumns' => [
                                                                         'file_id'           => (int) $insertId,
                                                                         'page_number'       => (int) $key + 1,
                                                                         'page_words'        => str_word_count( $result ),
                                                                         'page_text'         => $result,
                                                                         'page_resolution_x' => $this->ocrDpi,
                                                                         'page_resolution_y' => $this->ocrDpi,
                                                                     ],
                                                                 ] );
        }

        // check db entry
        if( !$pageInsertId )
        {
            QueueOcrModel::find()->id( $request->id )->update( [
                                                                   'updateColumns'  => [
                                                                       'error_message' => 'OCR page result was not saved in "processed_pages" table',
                                                                   ],
                                                                   'updateSpecials' => [
                                                                       'runs' => 'runs+1',
                                                                   ],
                                                               ] );
            return false;
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
                                                          'updateColumns' => [
                                                              'error_message' => 'file was not deleted',
                                                              'runs'          => 99,
                                                          ],
                                                      ] );
            return false;
        }

        // delete queue_ocr entry
        if( !QueueOcrModel::find()->id( $id )->delete() )
        {
            QueueOcrModel::find()->id( $id )->update( [
                                                          'updateColumns' => [
                                                              'error_message' => 'queue_ocr entry was not deleted',
                                                              'runs'          => 99,
                                                          ],
                                                      ] );
            return false;
        }

        return true;
    }
}