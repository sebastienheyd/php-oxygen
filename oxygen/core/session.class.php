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
    private static $_sessionActive = false;
    private static $_data;

    /**
     * Get instance of Session
     *
     * @return Session      Return singleton instance of Session
     */
    public static function init()
    {
        if (!isset(self::$_instance))
            self::$_instance = new self();
        return self::$_instance;
    }

    /**
     * @return Session
     */
    private function __construct()
    {
        $handler = Config::get('session.handler');

        if (in_array($handler, self::$_handlers))
        {
            $cname = 'f_session_' . ucfirst(strtolower($handler));

            $handler = new $cname(Config::get('session.maxlifetime', get_cfg_var("session.gc_maxlifetime")));

            session_set_save_handler(
                    array($handler, "open"), 
                    array($handler, "close"), 
                    array($handler, "read"), 
                    array($handler, "write"), 
                    array($handler, "destroy"), 
                    array($handler, "gc")
            );
        }

        self::_start();            
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
        self::$_data[$name] = $value;
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
        return isset(self::$_data[$name]) ? self::$_data[$name] : $defaultValue;
    }

    /**
     * Shortcut to unset value from session
     * 
     * @param string $name      Variable name to delete from session
     * @return void
     */
    public static function delete($name)
    {
        if(isset(self::$_data[$name])) unset(self::$_data[$name]);
    }

    /**
     * Start a new session
     *
     * @return bool      Return true if session is started
     */
    private static function _start()
    {
        if(!self::$_sessionActive)
        {
            session_name('O2_SESSION');
            self::$_sessionActive = session_start();
            self::$_data = $_SESSION;
            $_SESSION = array();
        }
        return self::$_sessionActive;
    }

    /**
     * Retrieve the current session id
     *
     * @return string       The current session id
     */
    public static function getId()
    {
        return session_id();
    }

    /**
     * Free all session variables
     * 
     * @return void
     */
    public static function clean()
    {
        self::$_data = array();
    }
    
    /**
     * Regenerate session id
     * 
     * @return bool
     */
    public static function regenerate()
    {
        return session_regenerate_id(true);
    }

    /**
     * Destroy the current session and start a new one
     * 
     * @return bool  Return true if session is detroyed
     */
    public static function destroy()
    {
        if (self::$_sessionActive)
        {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );

            session_destroy();
            self::$_data = array();
            self::$_sessionActive = false;
            return self::_start();
        }

        return false;
    }
    
    /**
     * When destroyed, write in session and close it
     */
    public function __destruct()
    {
        $_SESSION = self::$_data;
        session_write_close();
    }    
}