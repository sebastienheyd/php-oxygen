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
            $file = CONFIG_DIR.DS.'config.'.self::getConfigSuffix().'.xml';

            if(!is_file($file)) trigger_error($file.' does not exist', E_USER_ERROR);

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
     * Get current developper suffix, put it in a file named "user" one level under the project directory (PROJECT_DIR)
     *
     * @return string   The developper suffix to use (ex : config.sheyd.xml)
     */
    public static function getConfigSuffix()
    {
        try
		{
            $file = realpath(PROJECT_DIR.DS.'..'.DS.'user');

            $bd = explode(':', ini_get('open_basedir'));

            if(empty($bd) || (!in_array(PROJECT_DIR, $bd) && file_exists($file)))
            {
                $suffix = trim(file_get_contents($file));
                return $suffix != '' ? $suffix : 'default';
            }
            else
            {
                return 'default';
            }
        }
		catch(Exception $e)
		{
			return 'default';
		}
    }
}