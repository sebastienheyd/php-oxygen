<?php

/**
 * This file is part of the PHP Oxygen package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @copyright   Copyright (c) 2011 Sébastien HEYD <sheyd@php-oxygen.com>
 * @author      Sébastien HEYD <sheyd@php-oxygen.com>
 * @package     PHP Oxygen
 */

class Session
{
	private static $_instance;

    private $_lifeTime;
    private $_db;
    private $_tableName;
    private $_sessionActive = false;
    private $_dbConfig;

	/**
	 * @return Session
	 */
	private function __construct()
	{
        // check if session datas must be stored in database
        $config = Config::get('session');
       
        if(isset($config->handler) && $config->handler === 'database')
        {
            $this->_db = DB::getInstance(isset($config->db_config) ? $config->db_config : 'db1');
            $this->_tableName = Db::prefixTable(isset($config->table) ? $config->table : 'sessions');

            // create table if necessary
            if(!$this->_db->tableExists($this->_tableName, $config->db_config)) $this->_initSessionTable();

            $this->_lifeTime = get_cfg_var("session.gc_maxlifetime");
        }
        
        $this->start();
	}

	/**
	 * Get instance of Session
	 *
	 * @return Session      Return singleton instance of Session
	 */
	public static function getInstance()
	{
		if(!isset(self::$_instance)) self::$_instance = new self();
		return self::$_instance;
	}
    
    /**
     * Shortcut to set var in session
     * 
     * @param string $name      Variable name to define
     * @param mixed $value      Value of the variable
     * @return void
     */
    public static function set($name, $value)
    {
        self::getInstance()->$name = $value;
    }

    /**
     * Shortcut to get value from session
     * 
     * @param string $name          Variable name to get from session
     * @param mixed $defaultValue   [optional] Default value to return if variable does not exist in session. Default is null
     * @return mixed                Session variable value or default value
     */
    public static function get($name, $defaultValue = null)
    {
        $i = self::getInstance();        
        if(!isset($i->$name)) return $defaultValue;        
        return $i->$name;
    }
    
    /**
     * Shortcut to unset value from session
     * 
     * @param string $name      Variable name to delete from session
     * @return void
     */    
    public static function delete($name)
    {
         self::getInstance()->__unset($name);
    }
    
    /**
     * Start a new session
     *
     * @return boolean      Return true if session is started
     */
    public function start()
    {
        if(!$this->_sessionActive)
        {
            if($this->_db !== null) $this->setDbHandler();
            $this->_sessionActive = session_start();
        }
        return $this->_sessionActive;
    }

	/**
	 * Retrieve the current session id
	 *
	 * @return string       The current session id
	 */
	public function getId()
	{
		return session_id();
	}

    /**
     * Magic method used when writing data to inaccessible properties.
     * If value is null, the variable will be unset
     *
     * @see    http://www.php.net/manual/en/language.oop5.overloading.php
     * @example f_Session::getInstance()->mydata = 'my value' (will set $_SESSION['mydata])
     * 
     * @param string $name  Name of the variable to write into session
     * @param mixed $value  Value of the variable to insert
     * @return void
     */
    public function __set($name, $value)
    {
        if($value === null)
        {           
            if(isset($_SESSION[$name])) unset($_SESSION[$name]);
        }
        else
        {
            $_SESSION[$name] = $value;
        }
    }

    /**
     * Magic method used for reading data from inaccessible properties.
     * Equivalent to $_SESSION[$name]
     *
     * @link    http://www.php.net/manual/en/language.oop5.overloading.php
     * @example f_Session::getInstance()->mydata : will return $_SESSION['mydata']
     * 
     * @param string $name      Name of the property to get from the current session
     * @return  mixed|null      Return value of the property else null
     */
    public function __get($name)
    {
        return isset($_SESSION[$name]) ? $_SESSION[$name] : null;
    }

    /**
     * Magic method invoked by calling isset() or empty() on inaccessible properties.
     * Equivalent to isset($_SESSION[$name])
     *
     * @link    http://www.php.net/manual/en/language.oop5.overloading.php
     * @param   string $name        Name of the variable to check from the current session
     * @return  boolean             Return true if variable exist in the current session
     */
    public function __isset($name)
    {
        return isset($_SESSION[$name]);
    }

    /**
     * Magic method invoked when unset() is used on inaccessible properties.
     * Equivalent to unset($_SESSION[$name])
     *
     * @link    http://www.php.net/manual/en/language.oop5.overloading.php
     * @param   string $name        Name of the variable to remove from the current session
     */
    public function  __unset($name)
    {
        if(isset($_SESSION[$name])) unset($_SESSION[$name]);
    }

    /**
     * Magic method invoked when function does not exists in Session.
     * Works with set, get or unset functions
     *
     * @example Session:getInstance()->setMyVar('my value') : will set $_SESSION['myVar']
     * @example Session:getInstance()->getMyVar() : will get $_SESSION['myVar']
     * @example Session:getInstance()->unsetMyVar() : will unset $_SESSION['myVar']
     *
     * @param   string $method        Name of the called method must begin with get, set or unset
     * @param   array $argument       Arguments of the called method
     * @return  Session               Return current Session instance
     */
    public function __call($method, $argument)
    {
        if(preg_match('/^(set|get|unset)([A-Za-z0-9]?)$/', $method, $matches))
        {
            $name = lcfirst($matches[2]);
            switch ($matches[1])
            {
                case 'get':
                   if(isset($argument[0]))
                   return $this->$name;
                break;

                case 'set':
                    $this->$name = $argument[0];
                    return $this;
                break;

                case 'unset':
                    $this->$name = null;
                    return $this;
                break;
            
                default:
                    throw new BadMethodCallException('Method '.$method.' does not exist');
                break;
            }
        }

        return null;
    }

	/**
	 * Free all session variables
     * 
     * @return void
	 */
	public function clean()
	{
		session_unset();
	}

	/**
	 * Destroy the current session and start a new one
     * 
     * @return boolean  Return true if session is detroyed
	 */
	public function destroy()
	{
		if($this->_sessionActive)
        {
            session_destroy();            
            unset($_SESSION);
            $this->_sessionActive = false;
            $this->start();
            session_regenerate_id();            
            return true;
        }

        return false;
	}

// ======================================================= FOR SESSIONS IN DATABASE

    /**
     * Create session table when not exists and createauto is on
     * 
     * @return boolean  Return true if table was created
     */
    private function _initSessionTable()
    {
        $sql = 'CREATE TABLE `'.$this->_tableName.'` (
           `session_id` varchar(100) NOT NULL default "",
           `session_data` text NOT NULL,
           `expires` int(11) NOT NULL default "0",
            PRIMARY KEY  (`session_id`)
            ) ENGINE = MyIsam DEFAULT CHARSET=utf8 COLLATE utf8_unicode_ci;';

        return $this->_db->queryExec($sql) == 1;
    }

    /**
     * Sets current class methods as handler for session in database
     * @return void
     */
    public function setDbHandler()
    {
        $this->_dbConfig = Config::get('session', 'db_config');
        
        session_set_save_handler(
                array(&$this, "dbOpen"),
                array(&$this, "dbClose"),
                array(&$this, "dbRead"),
                array(&$this, "dbWrite"),
                array(&$this, "dbDestroy"),
                array(&$this, "dbGc")
        );
    }

    /**
     * Called when create new session
     *
     * @global string $sess_save_path
     * @param string $save_path
     * @param string $session_name
     * @return boolean
     */
    public function dbOpen($save_path, $session_name)
    {
        global $sess_save_path;
        $sess_save_path = $save_path;
        return true;
    }

    /**
     * Called when close session
     *
     * @return boolean
     */
    public function dbClose()
    {
        return true;
    }

    /**
     * Called when reading session content
     *
     * @param string $id        Id of the current session
     * @return mixed            Return session datas
     */
    public function dbRead($id)
    {
        $sql = 'SELECT `session_data` FROM `'.$this->_tableName.'` WHERE `session_id` = ? AND `expires` > ?';
        return $this->_db->prepare($sql)->execute($id, time())->fetchCol();
    }

    /**
     * Called when writing datas into session
     *
     * @param string $id        Id of the current session
     * @param mixed $data       Datas to insert into session
     * @return boolean          Return true if success
     */
    public function dbWrite($id, $data)
    {
        $sql = 'REPLACE `'.$this->_tableName.'` (`session_id`,`session_data`,`expires`) VALUES(?, ?, ?)';
        $this->_db->prepare($sql)->execute($id, $data, time() + $this->_lifeTime);
        return true;
    }

    /**
     * Called when destroy a session
     *
     * @param string $id        [optional] Id of a session. Default is null, then it get id from current session
     * @return boolean          Return true if session is correctly destroyed in database
     */
    public function dbDestroy($id = null)
    {
        if($id === null) $id = $this->getId();
        $res = $this->_db->prepare('DELETE FROM `'.$this->_tableName.'` WHERE `session_id`=?')->execute($id);
        return true;
    }

    /**
     * Clean up all expired sessions
     *
     * @return boolean      Return true if all expired sessions are deleted in database
     */
    public function dbGc()
    {
        $this->_db->prepare('DELETE FROM `'.$this->_tableName.'` WHERE `expires` < UNIX_TIMESTAMP();')->execute();
        return true;
    }
}