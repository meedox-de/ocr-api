<?php

namespace common\lib;

use Exception;
use mysql_xdevapi\Warning;


class FunctionalHelper
{
    /**
     * Returns the post value, or an empty string if no value was posted
     *
     * @param string $name
     *
     * @return string
     */
    public static function post(string $name) :string
    {
        $postValue = '';

        if( isset( $_POST[$name] ) )
        {
            $postValue = htmlspecialchars( trim( $_POST[$name] ) );
        }

        return $postValue;
    }


    /**
     * Returns the get value, or an empty string if no value was got
     *
     * @param string $name
     *
     * @return string
     */
    public static function get(string $name) :string
    {
        $getValue = '';

        if( isset( $_GET[$name] ) )
        {
            $getValue = htmlspecialchars( trim( $_GET[$name] ) );
        }

        return $getValue;
    }


    /**
     * Generates uniqid for submit-token check
     *
     * @return string
     */
    public static function submitToken() :string
    {
        return uniqid();
    }


    /**
     * Generates unique random string. Default length is 8 chars
     *
     * @param int $length
     *
     * @return string
     * @throws Exception
     */
    public static function getRandomString(int $length = 8) :string
    {
        return bin2hex( random_bytes( $length / 2 ) );
    }


    /**
     * Returns -1 if url is not reachable, otherwise returns the ping time in ms
     *
     * @param string $url
     * @param int    $port
     * @param int    $timeout
     *
     * @return int
     */
    public static function pingUrl(string $url, int $port = 80, int $timeout = 10) :?int
    {
        $start = microtime( true );

        set_error_handler( function($errno, $errstr)
        {}, E_WARNING );

        $file = fsockopen( $url, $port, $errno, $errstr, $timeout );
        restore_error_handler();

        if( !$file )
        {
            return null;
        }

        fclose( $file );
        return (int) ((microtime( true ) - $start) * 1000);
    }


}