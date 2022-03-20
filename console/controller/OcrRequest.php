<?php

namespace console\controller;

class OcrRequest
{
    public function __construct()
    {
        $responseArray = [];
        //$responseArray = array_merge($responseArray,[$_SERVER['REQUEST_METHOD']]);
        //$responseArray = array_merge($responseArray,$_GET);
        $responseArray = array_merge( $responseArray, $_POST );
        //$responseArray = array_merge($responseArray,$_COOKIE);

        $testString = '[{"id":1,"customer_id":1,"uploading_user":91,"transfer":0,"file_hash":"9a9903cb2a775db40f344e2dcf8a971c","file_name":"f05cd6ef4c3582b6.pdf","file_size":629974,"origin_file_name":"20210625-Nachtrag-25397419001.pdf","created_at":"2022-03-17 23:43:48","updated_at":"0000-00-00 00:00:00"},{"id":2,"customer_id":1,"uploading_user":91,"transfer":0,"file_hash":"f7c099d3af0a8c8f043089b67f0f9c27","file_name":"cb0cfe7c570530d4.pdf","file_size":32342,"origin_file_name":"rechnung_20041269_14.09.2021.pdf","created_at":"2022-03-17 23:43:48","updated_at":"0000-00-00 00:00:00"}]';

        header( 'Content-Type:application/json;charset=utf-8' );

        echo json_encode( $responseArray );
    }
}