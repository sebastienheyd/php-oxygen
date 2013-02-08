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
class Cache
{

    private static $_class;
    private static $_handlers = array('apc', 'memcache', 'file', 'null');

    /**
     * Get cache engine class instance to use
     * 
     * @return f_cache_Interface
     */
    private static function getClass()
    {
        // Instanciate only one time
        if (isset(self::$_class)) return self::$_class;

        // Check for authorized engines
        $handler = strtolower(Config::get('cache.handler', 'null'));
        if (!in_array($handler, self::$_handlers))
            trigger_error($handler . ' is not a valid cache handler');

        // Handler class name
        $class = 'f_cache_' . ucfirst($handler);

        // Instanciation
        self::$_class = new $class();

        // Return instance
        return self::$_class;
    }

    /**
     * Fetch data from cache
     *
     * @param 	string          Unique cache identifier
     * @return 	mixed|false		Data or false if none
     */
    public static function get($id)
    {
        return self::getClass()->get($id);
    }

    /**
     * Save content into cache
     * 
     * @param 	string		Unique cache identifier
     * @param 	mixed		Data to store
     * @param 	int			Cache lifetime in seconds
     * 
     * @return boolean  Return true on success
     */
    public static function save($id, $data, $ttl = 60)
    {
        return self::getClass()->save($id, $data, $ttl);
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

    /**
     * Check if handler is supported
     * 
     * @return boolean  Return true if handler is supported 
     */
    public static function isSupported($exception = true)
    {
        if ($exception)
            return self::getClass()->isSupported();

        try
        {
            return self::getClass()->isSupported();
        }
        catch (Exception $e)
        {
            return false;
        }
    }

}