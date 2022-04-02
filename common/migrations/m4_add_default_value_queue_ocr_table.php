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
        DBModel::execute()->sql( "ALTER TABLE `queue_ocr` CHANGE `runs` `runs` INT(1) NOT NULL DEFAULT '0';" );
    }

    /**
     * @return void
     */
    public static function down()
    {
        DBModel::execute()->sql( "ALTER TABLE `queue_ocr` CHANGE `runs` `runs` INT(1) NOT NULL;" );
    }
}