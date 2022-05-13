<?php

namespace common\migrations;

use common\models\DBModel;


class m5_add_file_extension_queue_ocr
{
    /**
     * @return void
     */
    public static function up()
    {
        DBModel::execute()->sql( "ALTER TABLE `queue_ocr` ADD `file_extension` ENUM('pdf','image') NOT NULL AFTER `file_name`;" );
    }

    /**
     * @return void
     */
    public static function down()
    {
        DBModel::execute()->sql( "ALTER TABLE `queue_ocr` DROP `file_extension`;" );
    }
}