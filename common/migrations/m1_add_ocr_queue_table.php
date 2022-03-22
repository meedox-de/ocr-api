<?php

namespace common\migrations;

use common\models\DBModel;


class m1_add_ocr_queue_table
{
    /**
     * @return void
     */
    public static function up()
    {
        DBModel::execute()->sql( "CREATE TABLE `ocr_api`.`queue_ocr` ( 
                                                                        `id` INT(11) NOT NULL AUTO_INCREMENT , 
                                                                        `source_api` INT(11) NOT NULL , 
                                                                        `source_id` INT(11) NOT NULL , 
                                                                        `file_name` VARCHAR(32) NOT NULL , 
                                                                        `created_at` DATETIME NOT NULL , 
                                                                        `updated_at` DATETIME NULL DEFAULT NULL , 
                                                                    PRIMARY KEY (`id`)) ENGINE = InnoDB;" );
    }

    /**
     * @return void
     */
    public static function down()
    {
        DBModel::execute()->sql( "DROP TABLE `queue_ocr`" );
    }
}