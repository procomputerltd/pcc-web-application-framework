<?php
namespace Procomputer\WebApplicationFramework\Db;

/*
 * Copyright (C) 2020 Pro Computer James R. Steel <jim-steel@pccglobal.com>
 * Pro Computer (pccglobal.com)
 * Tacoma Washington USA 253-272-4243
 *
 * This program is distributed WITHOUT ANY WARRANTY; without
 * even the implied warranty of MERCHANTABILITY or FITNESS FOR
 * A PARTICULAR PURPOSE. See the GNU General Public License
 * for more details.
 */

use Procomputer\Pcclib\Types;
use PDOStatement;
/**
 * 
 */
class Db {
    /**
     * 
     * @var PDO
     */
    public $connection = null;
    
    /**
     * 
     * @var string
     */
    public $lastError = '';
    
    /**
     * 
     * @var string
     */
    protected  $_dbConfig = [];
    
    /**
     * 
     * @var array
     */
    protected $_defaultConfig = [
        'host'     => '',
        'username' => '',
        'password' => '',
        'dbname'   => '',
        'port'     => 3306,
        'charset'  => '',
        'table'    => '',
        'unix_socket' => '',
        'dbprefix' => '',
        'sitename' => ''
    ];
    
    /**
     * 
     * @var type array
     */
    protected $_allowedOptions = [
        'columns' => '*',
        'order' => '',
        'maxcolumnlength' => 0,
        'maxrows' => 0
    ];
    
    /**
     * Ctor
     * 
     * @param array  $dbConfig
     */
    public function __construct(array $dbConfig) {
        $this->_dbConfig = $dbConfig;
    }

    /**
     * Create connection
     * @return boolean Returns true/false for connect success.
     */
    public function connect() {
        $dsn = $this->_constructDsn($this->_dbConfig);
        $this->connection = new \PDO($dsn, $this->_dbConfig['username'], $this->_dbConfig['password'], $this->_dbConfig['options'] ?? []); 
        if(! empty($this->_dbConfig['dbname'])) {
            $this->connection->query("use {$this->_dbConfig['dbname']}");
        }
        return true;
    }

    /**
     * Prepares from a string SQL statement a PDOStatement object ready to execute.
     * @param string $sql     SQL statement to prepare.
     * @param array  $options (optional) Prepare options.
     * @return PDOStatement Returns the PDOStatement or false on error
     * @throws \Throwable
     */
    public function prepare(string $sql, array $options = []) {
        if(! $this->connection) {
            $this->connect();
        }
        $error = false;
        try {
            return $this->connection->prepare($sql, $options);
        } catch (\Throwable $ex) {
            $error = true;
            $this->lasterror = $ex;
            throw $ex;
        }
        finally {
            if($error) {
                $this->close();
            }
        }
    }
    
    /**
     * Prepares and executes an SQL statement.
     * @param string $sql     SQL statement to execute.
     * @param array  $params  (optional) Execution parameters.
     * @param array  $options (optional) Prepare options.
     * @return boolean Returns true if success else false.
     * @throws \Throwable
     */
    public function exec(string $sql, array $params = null, array $options = []) {
        $statement = $this->prepare($sql, $options);
        return $statement->execute($params);
    }
    
    /**
     * Execute a database query on a SELECT type string SQL statement and returns rows.
     * @param string $sql
     * @param int    $mode    (optional) A 'PDO::FETCH_' mode constant.
     * @param array  $options (optional) Options passed to prepare()
     * @return array|boolean
     */
    public function query(string $sql, int $mode = \PDO::FETCH_ASSOC, array $options = []) {
        $statement = $this->prepare($sql, $options);
        $statement->execute();
        return $statement->fetchAll($mode);
    }
    
    /**
     * Retuns the number of records in a table.
     * @param  string $tableName   Table name.
     * @param  array $options     (optional) Options 
     * @return array|boolean
     */
    public function count(string $tableName, array $options = []) {
        // $supportedOptions = array_merge($this->_allowedOptions, array_intersect_key(array_change_key_case($options), $this->_allowedOptions));
        $sql = "SELECT COUNT(*) FROM `{$tableName}`";
        $allColumns = $this->query($sql);
        if(! is_array($allColumns)) {
            return false;
        }
        if(empty($allColumns)) {
            // ERROR
            $this->lastError = "An empty set of rows returned from table '{$tableName})";
            return false;
        }
        $row = reset((array)$allColumns);
        $return = intval(reset($row));
        return $return;
    }
    
    /**
     * Close PDO connection
     * @return self
     */
    public function close() {
        $this->connection = null;
        return $this;
    }
    
    /**
     * Returns a list of tables from a MySQL database.
     * @param array $options
     * @return array Returns a list of tables prioritized when $options['prioritize'] 
     *               contains a list of table names to list first
     */
    public function getTableList(array $options = []) {
        $options = array_change_key_case($options);
        $sql = "SHOW TABLES";
        $rows = $this->query($sql);
        // "SHOW TABLES" returns an array of sub-arrays.
        if(false === $rows) {
            return false; // Error message is in This->_lastError
        }
        if(! is_array($rows)) {
            return [];
        }
        $prioritize = $options['prioritize'] ?? null;
        if(! is_array($prioritize)) {
            $prioritize = (! is_string($prioritize) || Types::isBlank($prioritize)) ? false : [$prioritize];
        }
        elseif(empty($prioritize)) {
            $prioritize = false;
        }
        $return = [[],[]];
        foreach($rows as $row) {
            $a = (array)$row;
            $item = reset($a);
            $index = ($prioritize && false !== array_search($item, $prioritize)) ? 0 : 1;
            $return[$index][] = $item;
        }
        return array_merge(array_reverse($return[0]), $return[1]);
    }
    
    /**
     * return the connection property.
     */
    public function getConnection() {
        return $this->connection;
    }
    
    /**
     * Returns last error logged or empty if no error(s).
     * @return string
     */
    public function getLastError() {
        return $this->lastError;
    }
    
    /**
     * Constructs the MySQL PDO DSN.
     *
     * @param mixed[] $params
     */
    private function _constructDsn(array $params): string
    {
        $dsn = 'mysql:';
        if (isset($params['host']) && $params['host'] !== '') {
            $dsn .= 'host=' . $params['host'] . ';';
        }

        if (isset($params['port'])) {
            $dsn .= 'port=' . $params['port'] . ';';
        }

        if (isset($params['dbname'])) {
            $dsn .= 'dbname=' . $params['dbname'] . ';';
        }

        if (isset($params['unix_socket'])) {
            $dsn .= 'unix_socket=' . $params['unix_socket'] . ';';
        }

        if (isset($params['charset'])) {
            $dsn .= 'charset=' . $params['charset'] . ';';
        }

        return $dsn;
    }
   
}
