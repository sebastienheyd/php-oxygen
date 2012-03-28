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
    private static $_instance;

    /**
     * Get Config instance.
     *
     * @return Config   Return instance of Config (singleton)
     */
    public static function getInstance()
    {
		if(self::$_instance === null) self::$_instance = new self();
		return self::$_instance;
    }
    
    /**
     * Constructor 
     */
    private function __construct()
    {
        $file = CONFIG_DIR.DS.self::getEnvironment().'.ini';

        // die if no config file is found !
        if(!is_file($file)) die(str_replace(PROJECT_DIR, '', $file).' does not exist');
        
        $this->_fetch(parse_ini_file($file, true));
    }
    
    /**
     * Fetch all config ini vars into current object
     * 
     * @param array $array 
     */
    private function _fetch(array $array)
    {
        foreach($array as $section => $values)
        {
            if(is_array($values))
            {
                $this->$section = new stdClass();
                foreach($values as $k => $v)
                {
                    $this->$section->$k = $v;
                }
            }
        }
    }        
    
    /**
     * Get a configuration value
     * 
     * @param string $section       Name of the configuration section to get
     * @param string $name          Name of the parameter to get into the section. If null get section object
     * @param mixed $defaultValue   Default value if parameter is not found
     * @return defaultValue
     */
    public static function get($section, $name = null, $defaultValue = false)
    {
        $inst = self::getInstance();
        
        if($name === null)
        {
            if(isset($inst->$section)) $value = $inst->$section;               
        }
        else
        {
            if(isset($inst->$section->$name)) $value = $inst->$section->$name;     
        }
        
        if(!isset($value) || empty($value) || $value == '') return $defaultValue;
        
        return $value;
    }
    
    /**
     * Get the current environment name. To set an environment, define the environment var APP_ENV in your apache config or into the .htaccess file.<br />
     * ex : SetEnv APP_ENV "development"
     * 
     * @return string   The environment name or "default" (default value) if not set.
     */
    public static function getEnvironment()
    {
        defined('APP_ENV') || define('APP_ENV', (getenv('APP_ENV') ? strtolower(getenv('APP_ENV')) : 'default'));
        return APP_ENV;
    }
}