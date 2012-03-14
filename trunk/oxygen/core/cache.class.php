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

class Cache
{
    private static $_class;
    
    /**
     * Get cache engine class instance to use
     * 
     * @return f_cache_Driver 
     */
    private static function getClass()
    {
        // Instanciate only one time
        if(isset(self::$_class)) return self::$_class;
        
        // Check for authorized engines
        $authorized = array('apc', 'memcache', 'file', 'off');
        $config = strtolower(Config::get('cache', 'type', 'off'));        
        if(!in_array($config, $authorized)) trigger_error($config.' is not a valid cache system');

        // Special case for off = null
        if($config == 'off') $config = 'null';
        
        // Class name
        $class = 'f_cache_'.ucfirst($config);
        
        // Instanciation
        self::$_class = call_user_func(array($class, 'getInstance'));     
        
        // Return instance
        return self::$_class;
    }
   
    /**
	 * Fetch datas from cache
	 *
	 * @param 	string          Unique cache identifier
	 * @return 	mixed|false		Datas or false if none
	 */    
    public static function get($id)
    {
        return self::getClass()->get($id);
    }
    
	/**
	 * Save content into cache
     * 
	 * @param 	string		Unique cache identifier
	 * @param 	mixed		Datas to store
	 * @param 	int			Cache lifetime in seconds
     * 
     * @return boolean  Return true on success
     */    
    public static function save($id, $datas, $ttl = 60)
    {
        return self::getClass()->save($id, $datas, $ttl);
    }
    
	/**
	 * Delete from Cache
	 *
	 * @param 	string		Unique cache identifier
	 * @return 	boolean		Return true on success
	 */    
    public static function delete($id)
    {
        return self::getClass()->delete($id);
    }
    
    /**
     * Flush all cache
     * 
     * @return boolean  Return true on success 
     */    
    public static function flush()
    {
        return self::getClass()->flush();
    }
}