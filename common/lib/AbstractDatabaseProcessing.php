<?php

namespace common\lib;

use common\lib\DatabaseConnections;
use DateTime;
use PDO;
use PDOStatement;

/**
 * Class AbstractDatabaseProcessing
 *
 * @package common\lib
 */
abstract class AbstractDatabaseProcessing
{
    protected string $calledClass;
    protected PDO    $pdoConn;
    protected        $query;
    protected string $leftJoins           = ' ';
    protected string $selectString        = '';
    protected string $joinSelectedColumns = '';
    protected array  $whereValues         = [];
    protected array  $andWhereArray       = [];
    protected array  $andWhereString      = [];
    protected string $whereStatement      = '';
    protected string $groupByStatements   = '';
    protected string $orderByStatements   = '';
    protected string $limitStatement      = '';


    /**
     * AbstractDatabaseProcessing constructor.
     *
     * @param string $class
     */
    public function __construct(string $class)
    {
        $this->calledClass = $class;
        $this->pdoConn     = DatabaseConnections::preparePDO();
    }

    /**
     * @param string $sql
     *
     * @return bool
     */
    public function sql(string $sqlString) :bool
    {
        $query = $this->pdoConn->prepare( $sqlString );

        // Fehlerausgabe wenn Query incorect
        if( !$this->checkQueryIncorect( $query ) )
        {
            $this->message = '250';
            return false;
        }

        return $this->checkSqlExecuteSuccess( $query->execute( $this->whereValues ), $query );
    }

    /**
     * @param array $params
     *
     * @return false|string
     */
    public function insert(array $params) :false|string
    {
        $columnString = '';
        $valueString  = '';
        $executeArray = [];

        if( isset( $params['insertColumns'] ) )
        {
            foreach( $params['insertColumns'] as $columnName => $columnValue )
            {
                // Ab 2. Datensatz soll ein Komma eingefügt werden
                if( $columnString !== '' )
                {
                    $columnString .= ', ';
                    $valueString  .= ', ';
                }

                $columnString .= $columnName;
                $valueString  .= ':' . $columnName;

                $executeArray[$columnName] = $columnValue;
            }
        }

        if( isset( $params['insertSpecials'] ) )
        {
            foreach( $params['insertSpecials'] as $columnName => $columnValue )
            {
                if( $columnName === 'created_at' )
                {
                    continue;
                }

                // Ab 2. Datensatz soll ein Komma eingefügt werden
                if( $columnString !== '' )
                {
                    $columnString .= ', ';
                    $valueString  .= ', ';
                }

                $columnString .= $columnName;
                $valueString  .= $columnValue;
            }
        }

        // automatisch created_at spalte befüllen
        if( in_array( 'created_at', $this->calledClass::COLUMNS ) )
        {
            if( $columnString !== '' )
            {
                $columnString .= ', ';
                $valueString  .= ', ';
            }

            $columnString .= 'created_at';
            $valueString  .= 'NOW()';
        }

        $sql = $this->pdoConn->prepare( 'INSERT INTO ' . $this->calledClass::TABLE_NAME . ' (' . $columnString . ') VALUES (' . $valueString . ')' );

        // Fehlerausgabe wenn Query incorect
        if( !$this->checkQueryIncorect( $sql ) )
        {
            $this->message = '250';
            return false;
        }
        $this->checkSqlExecuteSuccess( $sql->execute( $executeArray ), $sql );

        return $this->pdoConn->lastInsertId();
    }

    /**
     * @param array $params
     *
     * @return bool
     */
    public function update(array $params) :bool
    {
        $valueString = '';

        $this->createWhereStatement();

        if( isset( $params['updateColumns'] ) )
        {
            foreach( $params['updateColumns'] as $columnName => $columnValue )
            {
                // Ab 2. Datensatz soll ein Komma eingefügt werden
                if( $valueString !== '' )
                {
                    $valueString .= ', ';
                }

                // wenn binded-param schon existiert, setze prefix davor
                $prefix = '';
                if( isset( $this->whereValues[$columnName] ) )
                {
                    $prefix = 'update_';
                }

                $valueString                              .= $columnName . ' = :' . $prefix . $columnName;
                $this->whereValues[$prefix . $columnName] = $columnValue;
            }
        }

        if( isset( $params['updateSpecials'] ) )
        {
            foreach( $params['updateSpecials'] as $columnName => $columnValue )
            {
                if( $columnName === 'updated_at' )
                {
                    continue;
                }

                // Ab 2. Datensatz soll ein Komma eingefügt werden
                if( $valueString !== '' )
                {
                    $valueString .= ', ';
                }

                $valueString .= $columnName . ' = ' . $columnValue;
            }
        }

        // automatisch updated_at spalte befüllen
        if( in_array( 'updated_at', $this->calledClass::COLUMNS ) )
        {
            if( $valueString !== '' )
            {
                $valueString .= ', ';
            }

            $valueString .= 'updated_at = NOW()';
        }

        /*
        var_dump( $this->pdoConn->prepare( 'UPDATE ' . $this->calledClass::TABLE_NAME . ' SET ' . $valueString . $this->whereStatement . $this->limitStatement) );
        var_dump( $this->whereValues );
        die();
        */

        $sql = $this->pdoConn->prepare( 'UPDATE ' . $this->calledClass::TABLE_NAME . ' SET ' . $valueString . $this->whereStatement . $this->limitStatement );

        // Fehlerausgabe wenn Query incorect
        if( !$this->checkQueryIncorect( $sql ) )
        {
            $this->message = '250';
            return false;
        }

        #TODO - Fehler beim speichern -> email/ log eintrag
        return $this->checkSqlExecuteSuccess( $sql->execute( $this->whereValues ), $sql );
    }

    /**
     * @return bool
     */
    public function delete() :bool
    {
        $this->createWhereStatement();
        #TODO - Fehler wenn kein Where Statement existiert
        /*
                var_dump( $this->pdoConn->prepare( 'DELETE FROM ' . $this->calledClass::TABLE_NAME . $this->whereStatement . $this->limitStatement ) );
                var_dump( $this->whereValues );
                die();
        */
        $sql = $this->pdoConn->prepare( 'DELETE FROM ' . $this->calledClass::TABLE_NAME . $this->whereStatement . $this->limitStatement );

        // Fehlerausgabe wenn Query incorect
        if( !$this->checkQueryIncorect( $sql ) )
        {
            $this->message = '250';
            return false;
        }

        #TODO - Fehler -> email/ log eintrag
        return $this->checkSqlExecuteSuccess( $sql->execute( $this->whereValues ), $sql );
    }

    /**
     * @param array $params
     *
     * @return $this
     */
    public function select(array $params) :self
    {
        if( $this->selectString !== '' )
        {
            $this->selectString .= ', ';
        }

        $count = 0;
        foreach( $params as $selectParam )
        {
            $this->selectString .= $selectParam;
            $count++;
            if( $count < count( $params ) )
            {
                $this->selectString .= ', ';
            }
        }

        return $this;
    }

    /**
     * @param string $table
     * @param string $joinReferenceColumn
     * @param string $ownReferenceColumn
     * @param array  $joinSelectedColumns
     *
     * @return $this
     */
    public function leftJoin(string $table, string $joinReferenceColumn, string $ownReferenceColumn, array $joinSelectedColumns) :self
    {

        $this->leftJoins .= ' LEFT JOIN ';
        $this->leftJoins .= $table . ' AS ' . $table;
        $this->leftJoins .= ' ON ' . $ownReferenceColumn . ' = ' . $table . '.' . $joinReferenceColumn;

        if( $this->joinSelectedColumns !== '' )
        {
            $this->joinSelectedColumns .= ', ';
        }
        $count = 0;
        foreach( $joinSelectedColumns as $column )
        {
            $this->joinSelectedColumns .= $table . '.' . $column . ' AS `' . $table . '.' . $column . '`';
            $count++;
            if( $count < count( $joinSelectedColumns ) )
            {
                $this->joinSelectedColumns .= ', ';
            }
        }

        return $this;
    }

    /**
     * @param array|string $clause
     * @param array        $values
     *
     * @return $this
     */
    public function andWhere(array|string $clause, array $values = []) :self
    {
        // array method
        if( is_array( $clause ) )
        {
            foreach( $clause as $whereKey => $whereValue )
            {
                $this->andWhereArray[$whereKey] = $whereValue;
            }
        }

        // string method
        if( is_string( $clause ) )
        {
            if( $clause !== '' )
            {
                $this->andWhereString[] = [
                    'clause' => $clause,
                    'values' => $values,
                ];
            }
        }

        return $this;
    }

    /**
     * @param array $params
     *
     * @return $this
     */
    public function groupBy(array $params) :self
    {
        if( $this->groupByStatements !== '' )
        {
            $this->groupByStatements .= ', ';
        }

        if( $this->groupByStatements === '' )
        {
            $this->groupByStatements = ' GROUP BY ';
        }

        $count = 0;
        foreach( $params as $groupParam )
        {
            $this->groupByStatements .= $groupParam;
            $count++;
            if( $count < count( $params ) )
            {
                $this->groupByStatements .= ', ';
            }
        }
        return $this;
    }

    /**
     * @param array $params
     *
     * @return $this
     */
    public function orderBy(array $params) :self
    {
        if( $this->orderByStatements !== '' )
        {
            $this->orderByStatements .= ', ';
        }

        if( $this->orderByStatements === '' )
        {
            $this->orderByStatements = ' ORDER BY ';
        }

        $count = 0;
        foreach( $params as $column => $sort )
        {
            switch( $sort )
            {
                case 3:
                    $sortString = ' DESC';
                    break;
                case 4:
                    $sortString = ' ASC';
                    break;
            }

            $this->orderByStatements .= $column . $sortString;

            $count++;
            if( $count < count( $params ) )
            {
                $this->orderByStatements .= ', ';
            }
        }

        return $this;
    }

    /**
     * @param int $amount
     *
     * @return $this
     */
    public function limit(int $amount = 1) :self
    {
        if( $amount > 0 )
        {
            $this->limitStatement = ' LIMIT ' . $amount;
        }

        return $this;
    }

    /**
     * @return false|mixed
     */
    public function one() :mixed
    {
        $this->createSelectStatement();
        $this->createWhereStatement();

        /*
        var_dump( $this->query . $this->leftJoins . $this->whereStatement . $this->groupByStatements . $this->orderByStatements );
        var_dump( $this->whereValues );
        die();
        */

        $sql = $this->pdoConn->prepare( $this->query . $this->leftJoins . $this->whereStatement . $this->groupByStatements . $this->orderByStatements . ' LIMIT 1' );
        // Fehlerausgabe wenn Query incorect
        if( !$this->checkQueryIncorect( $sql ) )
        {
            $this->message = '250';
            return false;
        }

        $this->checkSqlExecuteSuccess( $sql->execute( $this->whereValues ), $sql );
        return $sql->fetch( PDO::FETCH_OBJ );
    }

    /**
     * @return array|false
     */
    public function all() :array|false
    {
        $this->createSelectStatement();
        $this->createWhereStatement();

        /*
        var_dump( $this->query . $this->leftJoins . $this->whereStatement . $this->groupByStatements . $this->orderByStatements );
        var_dump( $this->whereValues );
        die();
        */

        $sql = $this->pdoConn->prepare( $this->query . $this->leftJoins . $this->whereStatement . $this->groupByStatements . $this->orderByStatements );

        // Fehlerausgabe wenn Query incorect
        if( !$this->checkQueryIncorect( $sql ) )
        {
            $this->message = '250';
            return false;
        }

        $this->checkSqlExecuteSuccess( $sql->execute( $this->whereValues ), $sql );
        return $sql->fetchAll( PDO::FETCH_OBJ );
    }

    /**
     * @return bool
     */
    public function count() :bool
    {
        $this->createSelectStatement();
        $this->createWhereStatement();

        $sql = $this->pdoConn->prepare( $this->query . $this->whereStatement );
        // Fehlerausgabe wenn Query incorect
        if( !$this->checkQueryIncorect( $sql ) )
        {
            $this->message = '250';
            return false;
        }
        $this->checkSqlExecuteSuccess( $sql->execute( $this->whereValues ), $sql );

        return $sql->rowCount();
    }

    /**
     * @return bool
     */
    public function exists() :bool
    {
        $this->createSelectStatement();
        $this->createWhereStatement();

        $sql = $this->pdoConn->prepare( $this->query . $this->whereStatement );
        // Fehlerausgabe wenn Query incorect
        if( !$this->checkQueryIncorect( $sql ) )
        {
            $this->message = '250';
            return false;
        }
        $this->checkSqlExecuteSuccess( $sql->execute( $this->whereValues ), $sql );

        if( $sql->fetch() )
        {
            return true;
        }
        return false;
    }

    /**
     * @return void
     */
    protected function createSelectStatement() :void
    {
        $selectString = '';
        $count        = 0;
        foreach( $this->calledClass::COLUMNS as $column )
        {
            $selectString .= $this->calledClass::TABLE_NAME . '.' . $column;
            $count++;
            if( $count < count( $this->calledClass::COLUMNS ) )
            {
                $selectString .= ', ';
            }
        }

        if( $this->joinSelectedColumns !== '' )
        {
            $selectString .= ', ' . $this->joinSelectedColumns;
        }

        if( $this->selectString !== '' )
        {
            $selectString .= ', ' . $this->selectString;
        }

        $this->query = 'SELECT ' . $selectString . ' FROM ' . $this->calledClass::TABLE_NAME . ' AS ' . $this->calledClass::TABLE_NAME;
    }

    /**
     * @return void
     */
    protected function createWhereStatement() :void
    {
        // where model array method
        if( !empty( $this->whereValues ) )
        {
            foreach( $this->whereValues as $whereKey => $whereValue )
            {
                if( $this->whereStatement !== '' )
                {
                    $this->whereStatement .= ' AND ';
                }

                if( $whereValue === null )
                {
                    // add to whereString
                    $this->whereStatement .= $this->calledClass::TABLE_NAME . '.' . $whereKey . ' is null';

                    // remove from bind Params
                    unset( $this->whereValues[$whereKey] );
                }
                else
                {
                    // add to whereString
                    $this->whereStatement .= $this->calledClass::TABLE_NAME . '.' . $whereKey . ' = :' . $whereKey;
                }
            }
        }

        // andWhereArray method
        if( !empty( $this->andWhereArray ) )
        {
            foreach( $this->andWhereArray as $whereKey => $whereValue )
            {
                if( $this->whereStatement !== '' )
                {
                    $this->whereStatement .= ' AND ';
                }

                if( $whereValue === null )
                {
                    // add to whereString
                    $this->whereStatement .= $whereKey . ' is null';
                }
                else
                {
                    // add to whereString
                    $whereKey = explode( '.', $whereKey );
                    if( count( $whereKey ) > 1 )
                    {
                        $whereKey = $whereKey[1];
                    }
                    else
                    {
                        $whereKey = $whereKey[0];
                    }

                    $this->whereStatement .= $whereKey . ' = :' . $whereKey;


                    // add to bind Params
                    $this->whereValues[$whereKey] = $whereValue;
                }
            }
        }

        // andWhere string method
        if( !empty( $this->andWhereString ) )
        {
            foreach( $this->andWhereString as $oneStatement )
            {
                // add to whereString
                if( $this->whereStatement !== '' )
                {
                    $this->whereStatement .= ' AND ';
                }
                $this->whereStatement .= $oneStatement['clause'];

                // add to bind Params
                foreach( $oneStatement['values'] as $key => $value )
                {
                    $this->whereValues[$key] = $value;
                }
            }
        }

        if( $this->whereStatement !== '' )
        {
            $this->whereStatement = ' WHERE ' . $this->whereStatement;
        }
    }

    /**
     * @param PDOStatement $sql
     *
     * @return bool
     */
    protected function checkQueryIncorect(PDOStatement $sql) :bool
    {
        if( !$sql )
        {
            if( $_SESSION['kunde_daten']->id === 1 )
            {
                var_dump( ' error:' );
                var_dump( $this->pdoConn->errorInfo() );
            }
            else
            {
                $errorInfo = '';
                foreach( $this->pdoConn->errorInfo() as $error )
                {
                    $errorInfo .= ' / ' . $error;
                }
                sendMail( MAIL_RECEIVER, 'Meedox Management Datenbank Fehler', 'DB SELECT Error: ' . $errorInfo );
            }
            return false;
        }

        return true;
    }

    /**
     * @param bool         $status
     * @param PDOStatement $query
     *
     * @return bool
     */
    protected function checkSqlExecuteSuccess(bool $status, PDOStatement $query) :bool
    {
        // query success
        if( $status )
        {
            return true;
        }

        // error case
        $content = 'Fehler bei SQL Query: "' . $query->queryString . '" <br> <br>';
        $content .= 'Query errorInfo(): <br>';
        foreach( $query->errorInfo() as $error )
        {
            $content .= '- ' . $error . '<br>';
        }
        $content .= '<br> <hr> <br>';
        $content .= 'Benutzer: ' . ($_SESSION['systemExecution'] ? 'systemExecution' : $_SESSION['benutzer_daten']->benutzer);
        $content .= '<br> Datum/Uhrzeit: ' . (new \DateTime)->format( 'd.m.Y H:i:s' );

        sendMail( MAIL_RECEIVER, 'Meedox Query Fehler', $content );

        return false;
    }
}