<?php

namespace common\migrations;

use common\models\DBModel;


class m3_add_error_column
{
    /**
     * @return void
     */
    public static function up()
    {
        DBModel::execute()->sql( "ALTER TABLE `queue_ocr` ADD `runs` INT(1) NOT NULL AFTER `file_name`;" );
        DBModel::execute()->sql( "ALTER TABLE `queue_ocr` ADD `error_message` TEXT NULL AFTER `runs`;" );
    }

    /**
     * @return void
     */
    public static function down()
    {
        DBModel::execute()->sql( "ALTER TABLE `queue_ocr` DROP `runs`;" );
        DBModel::execute()->sql( "ALTER TABLE `queue_ocr` DROP `error_message`;" );
    }
}