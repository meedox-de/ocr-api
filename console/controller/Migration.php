<?php

namespace console\controller;

use common\models\MigrationsModel;

class Migration
{
    public function __construct()
    {
        var_dump( MigrationsModel::find()->all() );
    }
}