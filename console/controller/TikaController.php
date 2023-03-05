<?php

namespace console\controller;

use common\lib\DebugHelper;
use Vaites\ApacheTika\Client;


class TikaController
{
    /**
     * @throws \Exception
     */
    public function __construct()
    {
        // deactivate time limit because there is no limit for the number of files being uploaded
        set_time_limit( 0 );

        $url = 'nas03';
        $port = 9998;
        $file = "test.pdf";

        //$client = Client::make('https://throemer.meedox.de:9997');
        $client = Client::make($url, $port);
        $client->setEncoding('UTF-8');
        $client->setTimeout(0);
        $client->setOCRLanguage('deu');
        //$client->setOCRLanguages(['deu', 'eng']);


        //var_dump($client->getLanguage($file));
        DebugHelper::pre($client->getMetadata($file));
        var_dump($client->getHTML($file));
        //var_dump($client->getHTML($file));
        //$text = $client->getText($file);
    }
}