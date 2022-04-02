<?php

namespace common\migrations;

use common\models\DBModel;


class m4_add_default_value_queue_ocr_table
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