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

class Config
{
    private static $_cache;              
    
    /**
     * Get a configuration value
     * 
     * @param string $section      Name of the configuration section to get
     * @param string $name         [optionnal] Name of the parameter to get into the section. If null get section object
     * @param mixed $defaultValue  [optionnal] Default value if parameter is not found. Default is false.
     * @return defaultValue
     */
    public static function get($section, $name = null, $defaultValue = false)
    {        
        if(self::$_cache === null) self::_fetch();        
        
        if($name === null)
        {
            if(isset(self::$_cache->$section)) $value = self::$_cache->$section;               
        }
        else
        {
            if(isset(self::$_cache->$section->$name)) $value = self::$_cache->$section->$name;     
        }
        
        if(!isset($value) || empty($value) || $value == '') return $defaultValue;
        
        return $value;
    }
    
    /**
     * Get the current environment name. <br />
     * To set an environment, define the environment var APP_ENV in your apache config or into the .htaccess file.<br />
     * ex : SetEnv APP_ENV "development"
     * 
     * @return string   The environment name or "default" (default value) if not set.
     */
    public static function getEnvironment()
    {
        defined('APP_ENV') || define('APP_ENV', (getenv('APP_ENV') ? strtolower(getenv('APP_ENV')) : 'default'));
        return APP_ENV;
    }
    
    /**
     * Fetch all config ini vars into current object
     * 
     * @param array $array 
     */
    private static function _fetch()
    {
        $file = CONFIG_DIR.DS.self::getEnvironment().'.ini';

        // die if no config file is found !
        if(!is_file($file)) die(str_replace(APP_DIR, '', $file).' does not exist');
        
        $array = parse_ini_file($file, true);
        
        self::$_cache = new stdClass();
        
        foreach($array as $section => $values)
        {
            if(is_array($values))
            {
                self::$_cache->$section = new stdClass();
                foreach($values as $k => $v)
                {
                    self::$_cache->$section->$k = $v;
                }
            }
        }
    }     
}