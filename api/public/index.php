<?php
var_dump("test-api");
die();
define( 'ROOT', dirname( __DIR__, 2 ) . DIRECTORY_SEPARATOR );

// api config
require_once('../config/directories.php');
require_once(CONFIG . 'routes.php');

// common directories
require_once(ROOT . 'common/global_config/config.php');


// autoloader
function autoload($class)
{
    $file = ROOT . str_replace( '\\', DIRECTORY_SEPARATOR, $class ) . '.php';

    if( file_exists( $file ) )
    {
        require_once $file;
    }
}

spl_autoload_register( 'autoload', true );

session_start();

// check class
$namespace  = 'api\\controller\\';
$controller = $routes[\common\lib\FunctionalHelper::get( 't' )] ?? false;

if( !$controller )
{
    die();
}
if( !class_exists( $namespace . $controller ) )
{
    die();
}

// call class
$_SESSION['systemExecution'] = true;
$callableClass               = $namespace . $controller;
new $callableClass();

// kill script
session_destroy();
die();