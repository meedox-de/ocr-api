<?php

################# Datenbankverbindungsdaten #################
const MYSQL_SERVER = 'localhost';
$dbName   = 'ocr_api';
$user     = 'root';
$password = '';

$configPdo = [
    'server_database' => 'mysql:host=' . MYSQL_SERVER . ';dbname=' . $dbName . ';charset=utf8',
    'user'            => $user,
    'password'        => $password,
];