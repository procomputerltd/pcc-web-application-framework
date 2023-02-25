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
/**
 * 
 */
class Db {
    const RGB_NAVY = '000080';
    const RGB_BLACK = '000000';
    const RGB_WHITE = 'ffffff';
    
    /**
     * 
     * @var string
     */
    public $lastError = '';
    
    /**
     * 
     * @var mysqli
     */
    public $connection = null;
            
    /**
     * Connection dsn (data source name)
     * @var string
     */
    protected $_dsn;

    /**
     * Connection username
     * @var string
     */
    protected $_username;

    /**
     * Connection password
     * @var string
     */
    protected $_password;

    /**
     * Connection database/dbName
     * @var string
     */
    protected $_database;

    /**
     * Connection options
     * @var array
     */
    protected $_options;

    /**
     * Driver name.
     * @var string
     */
    protected $_driver = '(n/a)';
    
    /**
     * Driver host name.
     * @var string
     */
    protected $_hostname = '';
    
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
     * @param string $dsn
     * @param string $username
     * @param string $password
     * @param string $database
     * @param array $options
     * @throws RuntimeException
     */
    public function __construct(string $dsn, string $username, string $password, string $database = '', array $options = []) {
        foreach(['dsn', 'username', 'password'] as $key) {
            if(Types::isBlank($$key)) {
                $msg = "{$key} parameter is empty; it is required.";
                throw new \RuntimeException($msg);
            }
        }
        $this->_dsn = $dsn;
        $this->_username = $username;
        $this->_password = $password;
        $this->_database = $database;
        $this->_options  = $options;
    }

    /**
     * Create connection
     * @return boolean Returns true/false for connect success.
     */
    public function connect() {
        try {
            if('mysqli' === $this->_dsn) {
                $this->_driver = 'mysqli';
                $this->_hostname = 'localhost';
                $driver = new \mysqli();
                $driver->connect('localhost', $this->_username, $this->_password, $this->_database);
                if(! $driver->connect_errno) {
                    $this->connection = $driver;
                    return true;
                }
                $errorMsg = $driver->connect_errno . ': ' . (empty($driver->connect_error) ? "an unknown error occurred" : $driver->connect_error);
            }
            else {
                $this->_driver = 'mysql';
                $this->connection = new \PDO($this->_dsn, $this->_username, $this->_password, $this->_options); 
                if(! empty($this->_database)) {
                    $this->connection->query("use {$this->_database}");
                }
                return true;
                /*
                $result = $pdo->query("SELECT * FROM TABLES WHERE TABLE_SCHEMA='information_schema'");
                $row = $result->fetch(PDO::FETCH_ASSOC);
                */
            }
        } catch (\Throwable $ex) {
            $errorMsg = "EXCEPTION: {$ex->getMessage()}";
        }
        $this->lastError = "ERROR: cannot connect PHP to MySQL database: " . $errorMsg;
        return false;
    }

    public function getDsnDescriptionString($includePassword = false) {
        $dsnDesc = $this->_driver .= ':' . $this->_username .= ':' . ($includePassword ? $this->_password : '(password_hidden)') . '@' 
            . $this->_hostname . '/' . ($this->_database ?? $this->_username);
        return $dsnDesc;
    }

    /**
     * @param  $tableName   Table name.
     * @param  $options     Options (optional)
     * @return array|boolean
     */
    public function fetch($tableName, array $options = []) {
        $supportedOptions = array_merge($this->_allowedOptions, array_intersect_key(array_change_key_case($options), $this->_allowedOptions));
        $sqlCols = Types::isBlank($supportedOptions['columns']) ? '*' : $supportedOptions['columns'];
        $cols = is_array($sqlCols) ? ('`' . implode('`, `', $sqlCols) . '`') : $sqlCols;
        $order = Types::isBlank($supportedOptions['order']) ? '' : (' ' . (string)$supportedOptions['order']);
        $maxrows = Types::isBlank($supportedOptions['maxrows']) ? 0 : $supportedOptions['maxrows'];
        $limit = '';
        if(is_numeric($maxrows)) {
            $maxrows = intval($maxrows);
            if($maxrows > 0) {
                $limit = ' LIMIT ' . $maxrows;
            }
        }
        $sql = "SELECT {$cols} FROM `{$tableName}`{$order}{$limit}";
        $allColumns = $this->query($sql);
        if(! is_array($allColumns)) {
            return false;
        }
        if(empty($allColumns)) {
            // ERROR
            $this->lastError = "An empty set of rows returned from table '{$tableName})";
            return false;
        }
        if(is_array($sqlCols)) {
            if(! empty($columns)) {
                $combined = array_combine($sqlCols, $sqlCols);
            }
        }
        $maxLength = is_numeric($supportedOptions['maxcolumnlength']) ? intval($supportedOptions['maxcolumnlength']) : 0;
        if($maxLength < 1) {
            $maxLength = 0;
        }
        $items = [];
        foreach($allColumns as $key => $row) {
            if(! $maxLength) {
                $items[$key] = $row;
                continue;
            }
            $rowArray = (array)$row;
            array_walk($rowArray, function(&$val) use($maxLength) {
                if(strlen($val) > $maxLength) {
                    $val = substr($val, 0, $maxLength - 3) . '...';
                }
            });
            $items[$key] = empty($combined) ? (object)$rowArray : (object)array_intersect_key($rowArray, $combined);
        }
        return $items;
    }
    
    /**
     * Retuns the number of records in a table.
     * @param  $tableName   Table name.
     * @param  $options     (optional) Options 
     * @return array|boolean
     */
    public function count($tableName, array $options = []) {
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
        $row = reset($allColumns);
        $return = intval(reset($row));
        return $return;
    }
    
    /**
     * Execute a database query on a string SQL statement and return 
     * @param string $sql
     * @return array|boolean
     */
    public function query($sql) {
        if(! $this->connection && ! $this->connect()) {
            return false;
        }
        try {
        /*            
         * query() returns false on failure. For successful SELECT, SHOW, DESCRIBE or EXPLAIN queries 
         * mysqli_query() will return a mysqli_result object. For other successful queries mysqli_query() 
         * will return true.            
         */
            $result = $this->connection->query($sql);
            if(!$result) {
                $this->lastError = $this->connection->errno . ': ' . $this->connection->error;
                unset($result);
            }
        } catch (\Throwable $ex) {
            $this->lastError = "EXCEPTION: {$ex->getMessage()}";
        }
        if(empty($result)) {
            return false;
        }
        /** @var PDOStatement $result */
        try {
            $rows = [];
            $method = 'fetchObject';
            if(! method_exists($result, $method)) {
                $method = 'fetch_object';
            }
            while($obj = $result->$method()) {
                $rows[] = $obj;
            }            
            return $rows;
        } catch (\Throwable $ex) {
            $this->lastError = "EXCEPTION: {$ex->getMessage()}";
        }
        return false;
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
            $item = reset($row);
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
     *
     * @param string|int $color      RGB Color specifier.
     * @param boolean    $addPrefix  (optional) Add the leading '#' to the result
     * 
     * @return string
     */
    function getContrastColor($color, $addPrefix = false) {
        // remove leading # and
        $rgbColor = is_string($color) 
            ? preg_replace('/^#+/', '', $color) 
            : substr('000000' . dechex((int)$color), -6);
        /**
         * If RBG parse-able value determine the luminosity and select contrasting black or white.
         */
        if(preg_match('/^([a-fA-F0-9]{3}|[a-fA-F0-9]{6})$/', $rgbColor)) {
            $rgb = [];
            $len = strlen($rgbColor);
            $incr = ($len < 6) ? 1 : 2;
            for($i = 0; $i < $len; $i += $incr) {
                $rgb[] = hexdec(substr($rgbColor, $i, $incr));
            }
            $squared_contrast = (
                $rgb[0] * $rgb[0] * .299 +
                $rgb[1] * $rgb[1] * .587 +
                $rgb[2] * $rgb[2] * .114
            );
            $result = ($squared_contrast > pow(130, 2)) ? self::RGB_BLACK : self::RGB_WHITE;
        }
        else {
            // The 
            $result = self::RGB_NAVY;
        }
        if($addPrefix) {
            $result = '#' . $result;
        }
        return $result;
    }
}
