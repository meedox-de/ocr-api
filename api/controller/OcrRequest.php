<?php

namespace api\controller;

class OcrRequest
{
    public function __construct()
    {
        $responseArray = [];
        $responseArray = array_merge($responseArray,[$_SERVER['REQUEST_METHOD']]);
        $responseArray = array_merge($responseArray,$_GET);
        $responseArray = array_merge($responseArray,$_POST);
        $responseArray = array_merge($responseArray,$_COOKIE);


        header('Content-Type:application/json;charset=utf-8');
        echo json_encode($responseArray);
    }
}