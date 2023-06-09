<?php

################# Directories #################
# common
const COMMON        = ROOT . 'common' . DIRECTORY_SEPARATOR;
const GLOBAL_CONFIG = COMMON . 'global_config' . DIRECTORY_SEPARATOR;
const LIB           = COMMON . 'lib' . DIRECTORY_SEPARATOR;
const MIGRATIONS    = COMMON . 'migrations' . DIRECTORY_SEPARATOR;

################# includes #################
require_once(GLOBAL_CONFIG . 'dbConn.php');
require_once(GLOBAL_CONFIG . 'errorReporting.php');
require_once(GLOBAL_CONFIG . 'apiConfig.php');

################# Zeitzone konfigurieren #################
//* wichtig um die Zeitumstellung auszuschließen.
date_default_timezone_set( 'UTC' );

################# Email Optionen #################
const MAIL_RECEIVER = 'm.throemer@gmail.com';

$mailSender = "From: OCR API <system@meedox.de>\n";
$mailSender .= "Content-Type: text/html\n";
define( 'EMAIL_SENDER', $mailSender );