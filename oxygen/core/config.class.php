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
class Config
{
    private static $_cache;

    /**
     * Get a configuration value
     * 
     * @param string $section      Name of the configuration section to get
     * @param mixed $defaultValue  [optionnal] Default value if parameter is not found. Default is false.
     * @return defaultValue
     */
    public static function get($section, $defaultValue = false)
    {
        if (self::$_cache === null)
            self::_fetch();

        if (strpos($section, '.') !== false)
            list($section, $name) = explode('.', $section);

        if (!isset($name))
        {
            if (isset(self::$_cache->$section))
                $value = self::$_cache->$section;
        }
        else
        {
            if (isset(self::$_cache->$section->$name))
                $value = self::$_cache->$section->$name;
        }

        if(isset($value) && $value === '') return false;
        
        if (!isset($value) || empty($value) || $value == '')
            return $defaultValue;

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
        // APP_ENV is defined manually
        if (defined('APP_ENV')) return APP_ENV;

        // APP_ENV is defined in vhost or htaccess
        if (getenv('APP_ENV'))
        {
            define('APP_ENV', getenv('APP_ENV'));
            return APP_ENV;
        }

        // APP_ENV is defined in a file outside the project dir (for CLI MODE only)
        if (CLI_MODE)
        {
            $file = realpath(APP_DIR . DS . ".." . DS . "APP_ENV");
            $bd   = explode(':', ini_get("open_basedir"));

            if (empty($bd) || (!in_array(APP_DIR, $bd) && is_file($file)))
            {
                $env = strtolower(trim(file_get_contents($file)));
                if ($env !== '') define('APP_ENV', $env);
                return $env;
            }
        }

        // APP_ENV = "default"
        define('APP_ENV', 'default');
        return 'default';
    }

    /**
     * Fetch all config ini vars into current object
     * 
     * @param array $array 
     */
    private static function _fetch()
    {
        $file = CONFIG_DIR . DS . self::getEnvironment() . '.ini';

        // copy default file to custom one
        if (!is_file($file)) copy(CONFIG_DIR . DS . 'default.ini', $file );

        $array = parse_ini_file($file, true);

        self::$_cache = new stdClass();

        foreach ($array as $section => $values)
        {
            if (is_array($values))
            {
                self::$_cache->$section = new stdClass();
                foreach ($values as $k => $v) self::$_cache->$section->$k = $v;
            }
        }
    }

}