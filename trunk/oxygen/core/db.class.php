<?php

/**
 * This file is part of the PHP Oxygen package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @copyright   Copyright (c) 2011-2012 Sébastien HEYD <sheyd@php-oxygen.com>
 * @author      Sébastien HEYD <sheyd@php-oxygen.com>
 * @package     PHP Oxygen
 */

class Db
{
    private $_query;
    private $_sql = '';
    
    private $_connexion;
    private $_hasActiveTransaction = false;
    private $_transactionSuccess = true;
    private $_transactions = 0;
    private $_transactionException;
    private $_parameters;    

    private $_executed = false;
    
    private static $_instances;   

    /**
     * Main constructor
     * 
     * @param string $config        Config to use from the current ini config file
     * @param type $throwException  Must the connexion return a exception or just false (for testing per example)
     * @return DB
     */
    protected function __construct($config, $throwException)
	{
        $config = Config::get($config);

        if($config === false) trigger_error('Database config '.$config.' does not exists in config file');       
        
        try
		{            
			$this->_connexion = new PDO($config->driver.':'.$config->type.'='.$config->host.';dbname='.$config->base,
			$config->login,
			$config->password,
			array(PDO::ATTR_PERSISTENT => (int) $config->persist));

			$this->_connexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $this->_connexion->exec('SET NAMES "UTF8"');
		}
		catch(PDOException $e)
		{
            if(!$throwException) return false;
            throw new PDOException($e->getMessage());    
		}
        return true;
	}
    
    /**
     * Display the current query string
     * 
     * @return string   The current SQL query
     */
    public function __toString()
    {
        return self::interpolateQuery($this->_sql, $this->_parameters);
    }

    /**
     * Get an instance of DB
     * 
     * @param string $config            [optional] Config to use from the current config file. Default is "db1"
     * @param string $throwException    [optional] Send an exception when connexion fail. Default is true. Set to false will return false on connexion error
     * @return DB|Exception|false       Return instance of DB when connexion succeed. Return an exception or false when connexion failed.
     */
    public static function getInstance($config = 'db1', $throwException = true)
    {           
        if(!isset(self::$_instances[$config]))
        {
            $class = new self($config, $throwException);
            self::$_instances[$config] = $class->checkConnexion() ? $class : false;
        }
        return self::$_instances[$config];
    }
    
    /**
     * Prepare a SQL query to execute
     * 
     * @param string $sql       The SQL query to execute
     * @param string $config    [optional] Database configuration to use from the current config file. Default is "db1"
     * @return DB               Instance of DB
     */
    public static function query($sql, $config = 'db1')
    {
        return self::getInstance($config)->prepare($sql);
    }
    
    /**
     * Execute an SQL query and return the number of affected rows
     * 
     * @param string $sql       The SQL query to execute
     * @param string $config    [optional] Database configuration to use from the current config file. Default is "db1"
     * @return integer          The number of affected rows
     */
    public static function exec($sql, $config = 'db1')
    {
        return self::getInstance($config)->queryExec($sql);
    }
    
    /**
     * Execute an SQL query from a file and return the number of affected rows
     * 
     * @param string $file      The SQL file to execute
     * @param string $config    [optional] Database configuration to use from the current config file. Default is "db1"
     * @return integer          The number of affected rows
     */
    public static function execFile($file, $config = 'db1', $restrictToDir = APP_DIR)
    {
        if(!strstr($file, $restrictToDir)) trigger_error('Security error : file is out of base directory', E_USER_ERROR);
        return self::getInstance(file_get_contents($filePath))->queryExec($sql);
    }
    
    /**
     * Execute an insert into a table
     * 
     * @param string $table     Table name to insert data into
     * @param array $values     Associative array of column names / values
     * @param string $config    [optional] Database configuration to use from the current config file. Default is "db1"
     * @return boolean          Return true if data are correctly inserted                        
     */
    public static function insert($table, array $values, $config = 'db1')
    {
        return f_db_Insert::getInstance($table, $values, $config)->execute();
    }    
    
    /**
     * Prepare an update query to execute
     * 
     * @param string $table         Name of the table to query on
     * @param array $values         Associative array of values
     * @return f_db_Update          Return an instance of f_db_Update
     */
    public static function update($table, array $values)
    {
        return f_db_Update::getInstance($table, $values);
    }
    
    /**
     * Prepare a select query to execute
     * 
     * @param string $fields    [optional] Column(s) name(s) comma separated (multiple method args). Default is empty (null).
     * @return f_db_Select      Return an instance of f_db_Select
     */
    public static function select($cols = null)
    {
        return f_db_Select::getInstance()->select(func_get_args());
    }
    
    /**
     * Prepare a select * from query to execute
     * 
     * @param string $table     Table to get results from
     * @return f_db_Select      Return an instance of f_db_Select
     */
    public static function selectFrom($table)
    {
        return f_db_Select::getInstance()->select()->from($table);
    }
    
    /**
     * Prepare a delete query to execute
     * 
     * @param string $table     Name of the table to query on
     * @return f_db_Delete      Return an instance of f_db_Delete
     */
    public static function deleteFrom($table)
    {
        return f_db_Delete::getInstance()->from($table);
    }

    /**
     * Check if connexion is available
     * 
     * @return boolean      Return true if connexion is ready and active
     */
    private function checkConnexion()
    {
        return $this->_connexion !== null;
    }

    // ================================================= TRANSACTION STATIC METHODS

    /**
     * Begin a new transaction
     * 
     * @param string $config    [optional] Database configuration to use from the current config file. Default is "db1"
     * @return boolean          Return true if transaction has correctly started 
     */
    public static function beginTransaction($config = 'db1')
    {
        return self::getInstance($config)->addTransaction();
    }
    
    /**
     * End a transaction. Will return true if committed and throw an exception with rollback if not
     * 
     * @param string $config            [optional] Database configuration to use from the current config file. Default is "db1"
     * @return boolean|PDOException     Return true if transaction has correctly ended else the thrown PDOException
     */
    public static function endTransaction($config = 'db1')
    {
        if(!self::commit($config)) self::throwTransactionException($config);
        return true;
    }    

    /**
     * Return the status of the current transaction
     * 
     * @param string $config    [optional] Database configuration to use from the current config file. Default is "db1"
     * @return boolean          Return true if transaction is active
     */
    public static function transactionStatus($config = 'db1')
    {
        return self::getInstance($config)->getTransactionStatus();
    } 
    
    /**
     * Throw the last transaction exeption if exists
     * 
     * @param string $config    [optional] Database configuration to use from the current config file. Default is "db1"
     */
    public static function throwTransactionException($config = 'db1')
    {
        $exc = self::getInstance($config)->getTransactionException();        
        if($exc === null) throw $exc;
    } 
    
    /**
     * Commit the current transaction operations
     * 
     * @param type $config      [optional] Database configuration to use from the current config file. Default is "db1"
     * @return boolean          Return true when commit success
     */
    public static function commit($config = 'db1')
    {
        return self::getInstance($config)->removeTransaction();
    }    

    /**
     * Rollback the current transaction operations
     * 
     * @param string $config    [optional] Database configuration to use from the current config file. Default is "db1"
     * @return boolean          Return true when rollback success
     */
    public static function rollBack($config = 'db1')
    {
        return self::getInstance($config)->revert();
    }    
    
// ================================================= TRANSACTION NON STATIC METHODS    
    
    /**
	 * Begin a new transaction, only effective on innoDb tables
     * 
     * @return boolean  Return true if transaction is added
	 */
	public function addTransaction()
	{
        // increase iterator
        $this->_transactions++;
        
        // we already have an open transaction
		if($this->_hasActiveTransaction) return false;
        
        // begin a new transaction
        $this->_hasActiveTransaction = $this->_connexion->beginTransaction();
        $this->_transactionSuccess = true;
        $this->_transactionException = null;
                
        // return transaction status
        return $this->_hasActiveTransaction;
	}   
    
    /**
     * Return current transaction status
     * 
     * @return boolean  Return true if there is an active transaction
     */
    public function getTransactionStatus()
    {
        return $this->_transactionSuccess;
    }    
    
    /**
     * Get the transaction exception if exists
     * 
     * @return null|PDOException    Return null if no exception was thrown else return PDOException
     */
    public function getTransactionException()
    {
        return $this->_transactionException;
    }    

	/**
	 * Commit the query result(s) or rollback if needed automatically
     * 
     * @return boolean  Return true if commit or rollback is a success
	 */
	public function removeTransaction()
	{
        // decrease iterator
        $this->_transactions--;

        if($this->_transactions === 0)
        {
            $this->_hasActiveTransaction = false;            
            if($this->_transactionSuccess) return $this->_connexion->commit();
            return !$this->_connexion->rollBack();
        }
	}    

	/**
	 * Rollback the query result(s)
     * 
     * @return void
	 */
	public function revert()
	{
        if($this->_hasActiveTransaction)
        {
            $this->_connexion->rollBack();
            $this->_hasActiveTransaction = false;
            $this->_transactions = 0;            
        }
	}

    // ================================================= QUERY

    /**
     * Execute an SQL query and return the number of affected rows
     * 
     * @param string $query     The SQL query to execute
     * @return integer          The number of affected rows
     */    
    public function queryExec($query)
    {
        if(strncasecmp('select', $query, 6) === 0) trigger_error('You must use Db::query() to return a SELECT query values', E_USER_NOTICE);
        $this->_sql = $query;
        return $this->_connexion->exec($query);
    }
    
    /**
     * Prepare a query to fetch
     *
     * @param string $query     The SQL query to prepare
     * @param array $params     Associative array of query keys/values
     * @return DB               Return an instance of DB
     */
    public function prepare($query)
    {
        $this->_sql = $query;
        $this->_query = $this->_connexion->prepare($query);
        return $this;
    }
    
    /**
     * Execute the prepared statement
     * 
     * @param type $parameters      [optional] Associative array of keys/values that will be used in the current prepared query. Default is empty (null)
     * @return DB                   Return an instance of DB
     */
    public function execute($parameters = null)
    {             
        if($parameters !== null) $parameters = func_num_args() > 1 ? func_get_args() : (array) $parameters;
        
        try 
        {
            $s = microtime(true);
            $this->_query->execute($parameters);
            if(Log::getInstance()->getLevel() >= Log::INFO) 
                Log::sql('{Db->execute()} ['.round((microtime(true) - $s) * 1000, 2).'ms] '.$this->interpolateQuery($this->_sql, $parameters));
        } 
        catch (PDOException $exc)
        {
            $exception = new PDOException($exc->getMessage()."<br /><br />".$this->_sql.'<br /><br />'.$this->_printVars($parameters).'<br />');
            if(!$this->_hasActiveTransaction) throw $exception;
            $this->_transactionException = $exception;
            $this->_transactionSuccess = false;
            return false;
        }

        $this->_executed = true;
        $this->_parameters = $parameters;
        
        return $this;
    }
    
    /**
     * Return given query parameters in html format
     * 
     * @param array $parameters     Associative array of keys/values that are used in a prepared query
     * @return string               Parameters in HTML format
     */
    private function _printVars($parameters)
    {
        if(empty($parameters)) return '';
        $txt = '';        
        foreach($parameters as $k => $v) $txt .= $k." = '".$v."'".'<br />';        
        return $txt;
    }
    
    // ================================================= RESULTS    
    
    /**
     * Count current query result(s)
     * 
     * @return integer      The number of rows (results)
     */
    public function count()
    {
        return $this->_query->rowCount();
    } 
    
    /**
     * Fetch all (multiple) results with the given fetch style
     *
     * @param integer $fetchStyle   [optional] Fetching type, use PDO static vars. Default is PDO::FETCH_ASSOC
     * @param string $args          [optional] Additionnal args used by some fetch style. Default is null
     * @return array                Return an array of results
     */
    public function fetchAll($fetchStyle = PDO::FETCH_ASSOC, $args = null)
    {      
        if(!$this->_executed) $this->execute();
        $result = $args === null ? $this->_query->fetchAll($fetchStyle): $this->_query->fetchAll($fetchStyle, $args);
		$this->_query->closeCursor();
        $this->_executed = false;
		return $result;
    }

    /**
     * Fetch first row with the given fetch style
     *
     * @param integer $fetchStyle   [optional] Fetching type, use PDO static vars. Default is PDO::FETCH_ASSOC
     * @param string $args          [optional] Additionnal args used by some fetch style. Default is null
     * @return array                Return the first row results as an array
     */
    public function fetch($fetchStyle = PDO::FETCH_ASSOC, $args = null)
    {
        if(!$this->_executed) $this->execute();

        if($args === null)
        {
            if($fetchStyle === PDO::FETCH_COLUMN)
            {
                $this->_query->setFetchMode($fetchStyle, 0);
            }
            else
            {
                $this->_query->setFetchMode($fetchStyle);                
            }
        }
        else
        {
            $this->_query->setFetchMode($fetchStyle, $args);   
        }
        
        $result = $this->_query->fetch($fetchStyle);
        $this->_query->closeCursor();
        $this->_executed = false;
        return $result;
    }

    /**
     * Fetch first row in an object with the given class name
     * 
     * @param string $className         [optional] The class to fetch result into. Default is 'stdClass'
     * @return stdClass|object|false    Return an object containing the current query results
     */
    public function fetchObject($className = 'stdClass')
    {        
        if(!$this->_executed) $this->execute();
        $result = $this->_query->fetchObject($className);
        $this->_query->closeCursor();
        $this->_executed = false;
        return $result;
    }
    
    // ================================================= ALIASES   
    
    /**
     * Alias of fetch(PDO::FETCH_COLUMN).
     * 
     * @return string            Return the column value 
     */
    public function fetchCol()
    {
        return $this->fetch(PDO::FETCH_COLUMN);
    }    
    
    /**
     * Alias of fetchAll(PDO::FETCH_COLUMN, $colNum). Will fetch result by a column number
     * 
     * @param integer $colNum   [optional] The column number to fetch. Default is null (0)
     * @return string           Return the column value 
     */
    public function fetchAllColumn($colNum = null)
    {
        return $this->fetchAll(PDO::FETCH_COLUMN, $colNum);
    }
    
    /**
     * Alias of fetchAll(PDO::FETCH_CLASS). Will fetch result into an array of objects.
     * 
     * @param strinf $className         [optional] The class name to instantiate with fetched values. Default is 'stdClass'
     * @param type $preloadConstructor  [optional] Must we preload the constructor before insert fetched data into the class. Default is false.
     * @return array                    Return an array of objects of the given class name
     */
    public function fetchAllObject($className = 'stdClass', $preloadConstructor = false)
    {
        return $preloadConstructor ? $this->fetchAll(PDO::FETCH_CLASS, $className) : $this->fetchAll(PDO::FETCH_CLASS|PDO::FETCH_PROPS_LATE, $className);
    }
    
    // ================================================= SHORTCUTS    
    
    /**
     * Delete all entries in a table (keep auto_increment value)
     * 
     * @param string $table     Table name
     * @param string $config    Config to use, mainly to get the prefix
     * @return integer          Number of removed lines
     */
    public function deleteAll($table, $config = 'db1')
    {
        return $this->queryExec('DELETE FROM '.self::quoteTable($table, $config));
    }

    /**
     * Delete all entries in a table (reset auto_increment value)
     * 
     * @param string $table     Table name
     * @param string $config    Config to use, mainly to get the prefix     
     * @return integer          Number of removed lines (only with InnoDb)
     */
    public function truncate($table, $config = 'db1')
    {
        return $this->queryExec('TRUNCATE TABLE '.self::quoteTable($table, $config));
    }
    
    // ================================================= GENERAL METHODS     

    /**
     * Check if given table exists in database
     * 
     * @param string $tableName     Table name
     * @param string $config        [optional] Config to use from the current config file. Default is "db1"
     * @return boolean              Return true if exists
     */
    public static function tableExists($tableName, $config = 'db1')
	{
		return DB::query('SHOW TABLES LIKE ?', $config)->execute($tableName)->fetchCol() !== false;
	}
    
    /**
     * Return tables list
     * 
     * @param string $prefix    [optional] Prefix of table name. Default is ""
     * @param string $config    [optional] Config to use from the current config file. Default is "db1"
     * @return array            Array of table names
     */
    public static function getTablesList($prefix = '', $config = 'db1')
    {
        $q = $prefix === '' ? 'SHOW TABLES' : 'SHOW TABLES LIKE "'.$prefix.'%"';
        return DB::query($q, $config)->fetchAllColumn();
    }
    
    /**
     * Return last inserted id
     * 
     * @return string
     */
    public function getLastId()
    {
        return $this->_connexion->lastInsertId();
    }
    
    // ================================================= STRING MANIPULATION

    /**
     * Checks if given string has an operator into
     *
     * @param string $sql   String to get operator from
     * @return boolean      true if string has an operator
     */
    public static function hasOperator($sql)
    {
        return preg_match("/(\s|<|>|!|=|is null|is not null|like|not like)/i", trim($sql)) > 0;
    }
    
    /**
     * Quote column identifier
     * 
     * @param string $var   Query string to autoquote identifiers
     * @return string       Autoquoted query string
     */
    public static function quoteIdentifier($var)
    {                   
        if($var === null || $var == '') trigger_error ('quoteIdentifier : string is null or empty', E_USER_ERROR);
        
        // parenthesis : return as is
        if(preg_match('/\((.*)\)|([^a-zA-Z0-9\.`\'\\s"])/i', $var)) return $var;
        
        // for schema
        if(strpos($var, '.') !== false)
        {
            $p = explode('.', $var);
            return join('.', array_map(array(__CLASS__, 'quoteIdentifier'), $p));
        }        
        
        // remove ` in string if necessary
        if(strpos($var, '`') !== false) return preg_replace('/`(.*)`/ise', 'self::quoteIdentifier("$1")', $var);             
        
        preg_match("/(.*)\s(.*)/is", $var, $matches);
              
        $operator = '';
        if(!empty($matches))
        {
            $var = trim($matches[1]);
            $operator = trim($matches[2]);
        }
        
        // var is an array set AS
        if(is_array($var))
        {
            $var = self::quoteIdentifier($var[0]).' AS '.self::quoteIdentifier($var[1]).' ';
        }
        else
        {
            $var = $var != '*' ? '`'.$var.'` ' : '*';
        }
        
        $var .= $operator;
        
        return $var;
    }
    
    /**
     * Quote table identifier
     * 
     * @param string $var       Query string to autoquote table names
     * @param string $config    Config to use, mainly for prefix
     * @return string           Autoquoted query string
     */
    public static function quoteTable($tableName, $config = 'db1')
    {       
        if($tableName === null || $tableName === '') trigger_error ('You have an error in your SELECT request, table name is empty', E_USER_ERROR);
        return self::quoteIdentifier(self::prefixTable($tableName, $config));
    } 
    
    /**
     * Prefix table name
     * 
     * @param string $tableName     Table name to prefix if necessary
     * @param string $config        Config to use
     * @return string               The prefixed table name
     */
    public static function prefixTable($tableName, $config = 'db1')
    {
        $prefix = Config::get($config.'.prefix', '');
        if($prefix !== '' && !preg_match('#^'.$prefix.'#', $tableName)) $tableName = $prefix.$tableName;            
        return $tableName;
    }
    
    /**
     * Escape string to insert
     * 
     * @param string $str   Query string to escape
     * @return string       Escaped query string
     */
    public static function escape($str) 
    {
        if(is_array($str)) return array_map(__METHOD__, $str);
        if(!empty($str) && is_string($str)) return '"'.str_replace(array('\\', "\0", "\n", "\r", "'", '"', "\x1a"), array('\\\\', '\\0', '\\n', '\\r', "\\'", '\\"', '\\Z'), $str).'"';
        return $str; 
    } 
    
    /**
     * Replace placeholders in a query with the value
     * 
     * @param string $query     The sql query
     * @param array $params     Array of parameters
     * @return string           The query with placeholders replaced by values
     */
    public static function interpolateQuery($query, $params) 
    {
        if(!is_array($params)) return $query;
        
        $keys = array();

        foreach ($params as $key => $value) 
        {
            $keys[] = is_string($key) ? '/:'.$key.'/' : '/[?]/';
            $params[$key] = is_string($value) ? self::escape($value) : $value;
        }

        return preg_replace($keys, $params, $query, 1);
    }
    
    /**
     * Return interpolated latest query string
     * 
     * @return string   Current query string
     */
    public function getLastQuery()
    {
        return $this->__toString();
    }
    
}
