<?php

namespace common\migrations;

use common\models\DBModel;
use common\models\OcrServerModel;


class m7_add_in_process_column
{
    /**
     * @return void
     */
    public static function up() :void
    {
        DBModel::execute()->sql( "ALTER TABLE `queue_ocr` ADD `in_process` DATETIME NULL AFTER `file_extension`;" );
        DBModel::execute()->sql( "ALTER TABLE `queue_ocr` ADD `server_id` int(11) NULL AFTER `in_process`;" );

        DBModel::execute()->sql( "ALTER TABLE `processed_pages` CHANGE `page_resolution_x` `page_resolution_x` INT(4) NULL;" );
        DBModel::execute()->sql( "ALTER TABLE `processed_pages` CHANGE `page_resolution_y` `page_resolution_y` INT(4) NULL;" );

        DBModel::execute()->sql( "ALTER TABLE `processed_pages` ADD CONSTRAINT `fk_processed_pages__file_id` FOREIGN KEY (`file_id`) REFERENCES `processed_files`(`id`) ON DELETE CASCADE ON UPDATE RESTRICT;" );


        DBModel::execute()->sql( "CREATE TABLE `ocr_server` (
                                                                      `id` INT(11) NOT NULL AUTO_INCREMENT,
                                                                      `name` varchar(250) NOT NULL,
                                                                      `url` varchar(250) NOT NULL,
                                                                      `port` int(11) NOT NULL,
                                                                      `document_in_process_count` int(11) NOT NULL DEFAULT 0,
                                                                      `document_total_count` int(11) NOT NULL DEFAULT 0,
                                                                      `created_at` DATETIME NOT NULL , 
                                                                      `updated_at` DATETIME NULL DEFAULT NULL , 
                                                                PRIMARY KEY (`id`)) ENGINE = InnoDB;" );

        DBModel::execute()->sql( "ALTER TABLE `queue_ocr` ADD CONSTRAINT `fk_queue_ocr__server_id` FOREIGN KEY (`server_id`) REFERENCES `ocr_server`(`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;" );

        OcrServerModel::find()->insert( [
                                            'insertColumns' => [
                                                'name' => 'apache-tika1',
                                                'url'  => 'throemer.meedox.de',
                                                'port' => 9998,
                                            ],
                                        ] );
        OcrServerModel::find()->insert( [
                                            'insertColumns' => [
                                                'name' => 'apache-tika2',
                                                'url'  => 'srv.vohburger-hauskrankenpflege.de',
                                                'port' => 9998,
                                            ],
                                        ] );

        // create new table "ping_server_log"
        DBModel::execute()->sql( "CREATE TABLE `ping_server_log` (
                                                                      `id` INT(11) NOT NULL AUTO_INCREMENT,
                                                                      `server_id` int(11) NOT NULL,
                                                                      `ping` int(11) NULL DEFAULT NULL,
                                                                      `created_at` DATETIME NOT NULL ,
                                                                      `updated_at` DATETIME NULL DEFAULT NULL , 
                                                                PRIMARY KEY (`id`)) ENGINE = InnoDB;" );

        // create foreign key for "ping_server_log"
        DBModel::execute()->sql( "ALTER TABLE `ping_server_log` ADD CONSTRAINT `fk_ping_server_log__server_id` FOREIGN KEY (`server_id`) REFERENCES `ocr_server`(`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;" );
    }

    /**
     * @return void
     */
    public static function down() :void
    {
        DBModel::execute()->sql( "ALTER TABLE `ping_server_log` DROP FOREIGN KEY `fk_ping_server_log__server_id`" );
        DBModel::execute()->sql( "DROP TABLE `ping_server_log`" );

        DBModel::execute()->sql( "ALTER TABLE `queue_ocr` DROP FOREIGN KEY `fk_queue_ocr__server_id`" );
        DBModel::execute()->sql( "DROP TABLE `ocr_server`" );

        DBModel::execute()->sql( "ALTER TABLE `processed_pages` DROP FOREIGN KEY `fk_processed_pages__file_id`" );

        DBModel::execute()->sql( "ALTER TABLE `processed_pages` CHANGE `page_resolution_y` `page_resolution_y` INT(4) NOT NULL;" );
        DBModel::execute()->sql( "ALTER TABLE `processed_pages` CHANGE `page_resolution_x` `page_resolution_x` INT(4) NOT NULL;" );

        DBModel::execute()->sql( "ALTER TABLE `queue_ocr` DROP `server_id`;" );
        DBModel::execute()->sql( "ALTER TABLE `queue_ocr` DROP `in_process`;" );
    }
}