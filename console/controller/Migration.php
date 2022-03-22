<?php

namespace console\controller;

use common\models\MigrationPlanModel;
use common\models\MigrationsModel;


class Migration
{
    public function __construct()
    {
        self::checkForNewAndExistingMigrations();
        self::executeMigrations();
    }

    /**
     * add new migration files to migration_plan table
     * delete migrations from db without migration files
     *
     * @return void
     */
    private static function checkForNewAndExistingMigrations() :void
    {
        $migrations = self::checkMigrationDirectory();

        // Migrations without an associated migration file will be deleted
        foreach( MigrationPlanModel::find()->all() as $migrationEntry )
        {
            // if migration file and db entry exists, continue
            if( in_array( $migrationEntry->name, $migrations ) )
            {
                continue;
            }

            // delete when migration was executed
            if( MigrationsModel::find()->name( $migrationEntry->name )->exists() )
            {
                MigrationsModel::find()->name( $migrationEntry->name )->delete();
            }

            // entry delete in migration_plan table
            MigrationPlanModel::find()->id( $migrationEntry->id )->limit( 1 )->delete();
        }
    }

    /**
     * search in directories for migration files
     *
     * @return array
     */
    private static function checkMigrationDirectory() :array
    {
        $migrationNames = [];

        foreach( scandir( MIGRATIONS ) as $filename )
        {
            // continue when no .php file
            if( is_dir( $filename ) || !str_ends_with( $filename, '.php' ) )
            {
                continue;
            }


            $migrationName    = substr( $filename, 0, -4 );
            $migrationNames[] = $migrationName;

            // continue when migration in db exists
            if( MigrationPlanModel::find()->name( $migrationName )->exists() )
            {
                continue;
            }

            MigrationPlanModel::find()->insert( [
                                                    'insertColumns' => [
                                                        'name' => $migrationName,
                                                    ],
                                                ] );
        }

        return $migrationNames;
    }

    /**
     * executed all migrations (up/down)
     *
     * @return void
     */
    private static function executeMigrations() :void
    {
        $migrationsPlan = MigrationPlanModel::find()->active( true )->all();

        foreach( $migrationsPlan as $migrationPlan )
        {
            // skip disabled migrations
            if( !$migrationPlan->active )
            {
                continue;
            }

            $migration = MigrationsModel::find()->name( $migrationPlan->name )->one();

            // migration doesnt exists
            if( !$migration && $migrationPlan->direction === MigrationPlanModel::MIGRATION_DIRECTION_UP )
            {
                // if execution true, save it to db
                if( self::executeMigration( $migrationPlan ) )
                {
                    MigrationsModel::find()->insert( [
                                                         'insertColumns' => [
                                                             'name'     => $migrationPlan->name,
                                                             'executed' => $migrationPlan->direction,
                                                         ],
                                                     ] );
                }
            }

            // migration exists - check if the direction has changed and execute the migration
            if( $migration !== false && $migrationPlan->direction !== $migration->executed )
            {
                // if execution true, update it in db
                if( self::executeMigration( $migrationPlan ) )
                {
                    MigrationsModel::find()->name( $migration->name )->update( [
                                                                                   'updateColumns' => [
                                                                                       'executed' => $migrationPlan->direction,
                                                                                   ],
                                                                               ] );
                }
            }
        }
    }

    /**
     * @param object $migration
     *
     * @return bool
     */
    private static function executeMigration(object $migration) :bool
    {
        $class = 'common\migrations\\' . $migration->name;

        switch( $migration->direction )
        {
            case MigrationPlanModel::MIGRATION_DIRECTION_UP:
                $class::up();
                break;

            case MigrationPlanModel::MIGRATION_DIRECTION_DOWN:
                $class::down();
                break;
        }

        return true;
    }
}