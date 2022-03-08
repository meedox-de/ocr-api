<?php

namespace common\lib;


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
}