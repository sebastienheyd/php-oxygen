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

class Config extends SimpleXMLElement
{
    private static $_instance;

    /**
     * Get Config instance.
     *
     * @return Config   Return instance of Config (singleton)
     */
    public static function getInstance()
    {
		if(is_null(self::$_instance))
		{
            $file = CONFIG_DIR.DS.'config.'.self::getEnvironment().'.xml';

            // die if no config file is found !
            if(!is_file($file)) die(str_replace(PROJECT_DIR, '', $file).' does not exist');

            self::$_instance = simplexml_load_file($file, 'Config');
		}
		return self::$_instance;
    }

    /**
     * Get a config value, return default value if not found
     * 
     * @param string $name       Name of the config parameter to get
     * @param string $default    [optional] Default value if requested config parameter is not found. Defaut is null.
     * @return mixed
     */
    public function get($name, $default = null)
    {
        return isset($this->$name) ? (string) $this->$name : $default;
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