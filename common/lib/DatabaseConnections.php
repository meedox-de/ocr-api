<?php

namespace common\lib;

use PDO;
use PDOException;

class DatabaseConnections
{
    public static PDO $pdo;

    /**
     * @return PDO
     */
    public static function preparePDO() :PDO
    {
        global $configPdo;

        try
        {
            self::$pdo = new PDO( $configPdo['server_database'], $configPdo['user'], $configPdo['password'] );
        }
        catch( PDOException $error )
        {
            MailHelper::send( MAIL_RECEIVER, 'Meedox WEB Fehler - Management DB nicht erreichbar', '<p>Beim Verbinden zur DB ist ein Fehler aufgetreten. Fehler: </p>' );
            die();
        }

        // Ausschalten der Emulation von PDO - Prepares
        self::$pdo->setAttribute( PDO::ATTR_EMULATE_PREPARES, false );
        self::$pdo->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING );

        return self::$pdo;
    }
}