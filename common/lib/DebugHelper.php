<?php

namespace common\lib;


class DebugHelper
{
    public static function pre( $value) :void
    {
        echo '<pre>';
        print_r( $value );
        echo '</pre>';
    }
}