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


// Include core path
set_include_path(get_include_path().PATH_SEPARATOR.FW_DIR.DS.'core'.PATH_SEPARATOR.FW_DIR.DS.'lib'.PATH_SEPARATOR.PROJECT_DIR.DS.'model');

// Define file extensions to get
spl_autoload_extensions('.class.php');

// First try to load from core
spl_autoload_register();

// Or load from defined autoload
spl_autoload_register('Autoload::load');

class Autoload
{
    /**
     * Magic __autoload method
     * See : http://www.php.net/autoload
     * 
     * @param string $class_name    The class name to get
     */
    public static function load($className)
    {
        // specific case for Smarty 3
        if(strstr($className, 'Smarty'))
        {
            $_class = strtolower($className);
            if (substr($_class, 0, 16) === 'smarty_internal_' || $_class == 'smarty_security') require_once FW_DIR.DS."lib".DS.'vendor'.DS."smarty".DS.'sysplugins'.DS. $_class . '.php';
            return;
        }

        $path = class_file_path($className);

        // check if file exists
        if(!$path) throw new Exception('Cannot load file for class '.$className);

        //if true load the file
        require_once($path);
    }
    
}


