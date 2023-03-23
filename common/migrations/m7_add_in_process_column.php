<?php

namespace common\migrations;

use common\models\DBModel;


class m7_add_in_process_column
{
    /**
     * @return void
     */
    public static function up() :void
    {
        DBModel::execute()->sql( "ALTER TABLE `queue_ocr` ADD `in_process` TINYINT(1) NOT NULL DEFAULT '0' AFTER `file_extension`;" );
    }

    /**
     * @return void
     */
    public static function down() :void
    {
        DBModel::execute()->sql( "ALTER TABLE `queue_ocr` DROP `in_process`;" );
    }
}