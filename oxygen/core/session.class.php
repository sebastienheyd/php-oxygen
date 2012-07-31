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

class Session
{
	private static $_instance;

    private static $_handlers = array('database', 'files', 'cookie', 'memcached');
    
    private $_sessionActive = false;

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
	 * @return Session
	 */
	private function __construct()
	{
        $handler = Config::get('session.handler');
        
        if(in_array($handler, self::$_handlers))
        {
            $cname = 'f_session_'.ucfirst(strtolower($handler));
            
            $handler = new $cname(Config::get('session.maxlifetime',get_cfg_var("session.gc_maxlifetime")));
            
            session_set_save_handler(
                array($handler, "open"),
                array($handler, "close"),
                array($handler, "read"),
                array($handler, "write"),
                array($handler, "destroy"),
                array($handler, "gc")
            );
        }                
        
        $this->start();
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
        $_SESSION[$name] = $value;
    }

    /**
     * Shortcut to get value from session
     * 
     * @param string $name          Variable name to get from session
     * @param mixed $defaultValue   [optional] Default value to return if variable does not exist in session. Default is false
     * @return mixed                Session variable value or default value
     */
    public static function get($name, $defaultValue = false)
    {
        return isset($_SESSION[$name]) ? $_SESSION[$name] : $defaultValue;
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
        if(!$this->_sessionActive) $this->_sessionActive = session_start();
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
     * When destroyed, close session
     */
    public function __destruct()
    {
        session_write_close();
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
}