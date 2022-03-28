<?php

namespace common\migrations;

use common\models\DBModel;


class m2_add_ocr_processed_table
{
    /**
     * @return void
     */
    public static function up()
    {
        DBModel::execute()->sql( "CREATE TABLE `processed_files` (
                                                                      `id` INT(11) NOT NULL AUTO_INCREMENT,
                                                                      `source_api` int(11) NOT NULL,
                                                                      `source_id` int(11) NOT NULL,
                                                                      `file_name` varchar(32) COLLATE utf8mb4_bin NOT NULL,
                                                                      `file_pages` int(11) NOT NULL,
                                                                      `file_words` int(11) NOT NULL,
                                                                      `ocr_time` varchar(10) COLLATE utf8mb4_bin NOT NULL,
                                                                      `created_at` DATETIME NOT NULL , 
                                                                      `updated_at` DATETIME NULL DEFAULT NULL , 
                                                                PRIMARY KEY (`id`)) ENGINE = InnoDB;" );

        DBModel::execute()->sql( "CREATE TABLE `processed_pages` ( 
                                                                        `id` INT(11) NOT NULL AUTO_INCREMENT , 
                                                                        `file_id` INT(11) NOT NULL , 
                                                                        `page_number` INT NOT NULL , 
                                                                        `page_words` INT NOT NULL , 
                                                                        `page_text` TEXT NOT NULL , 
                                                                        `page_resolution_x` int(4) NOT NULL , 
                                                                        `page_resolution_y` int(4) NOT NULL , 
                                                                        `created_at` DATETIME NOT NULL , 
                                                                        `updated_at` DATETIME NULL DEFAULT NULL , 
                                                                PRIMARY KEY (`id`)) ENGINE = InnoDB;" );
    }

    /**
     * @return void
     */
    public static function down()
    {
        DBModel::execute()->sql( "DROP TABLE `processed_files`" );
        DBModel::execute()->sql( "DROP TABLE `processed_pages`" );
    }
}