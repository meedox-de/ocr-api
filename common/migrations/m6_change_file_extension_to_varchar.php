<?php

namespace common\migrations;

use common\models\DBModel;


class m6_change_file_extension_to_varchar
{
    /**
     * @return void
     */
    public static function up()
    {
        DBModel::execute()->sql( "ALTER TABLE `queue_ocr_api` CHANGE `file_extension` `file_extension` VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL;" );
    }

    /**
     * @return void
     */
    public static function down()
    {
        DBModel::execute()->sql( "ALTER TABLE `queue_ocr_api` CHANGE `file_extension` `file_extension` VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL;" );
    }
}