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

define('SMARTY_SPL_AUTOLOAD', 1);
define('PS', PATH_SEPARATOR);

// include paths
set_include_path(HOOKS_DIR.DS.'core'.PS.
                 HOOKS_DIR.DS.'lib'.PS.
                 HOOKS_DIR.DS.'lib'.DS.'vendor'.DS.'smarty'.DS.'sysplugins'.PS.
                 FW_DIR.DS.'core'.PS.
                 FW_DIR.DS.'lib'.PS.
                 FW_DIR.DS.'lib'.DS.'vendor'.DS.'smarty'.DS.'sysplugins'.PS.
                 APP_DIR.DS.'model'.PS.
                 get_include_path());

// define file extensions to get
spl_autoload_extensions('.class.php,.php');

// first try to load from included paths
spl_autoload_register();

// or load from autoload method
spl_autoload_register('Autoload::load');

class Autoload
{
    private static $_cache = array();
    
    public static function load($className)
    {
        if($path = self::_getClassPath($className)) require_once($path);
    }
    
    /**
     * Add a path to the SPL autoload include path
     * @param string|array $path     A folder path or array of path
     */
    public static function addPath($path)
    {
        foreach((array) $path as $p)
        {
            set_include_path(get_include_path().PS.$p);
        }        
    }
    
    /**
     * Get correct file to include from a class name
     * Used by __autoload()
     * 
     * @param string $className     Input class name
     * @param string $ext           [optional] Class file extension to use. Default is 'class.php'
     * @return string|false         Return a path or false if no file is found
     */
    private static function _getClassPath($className, $ext = 'class.php')
    {
        if(isset(self::$_cache[$className])) return self::$_cache[$className];

        $mustCache = Config::get('cache.autoload');
        
        $cache = CACHE_DIR.DS.'autoload.cache';
        
        if($mustCache && is_file($cache))
        {
            self::$_cache = unserialize(file_get_contents($cache));
            if(isset(self::$_cache[$className])) return self::$_cache[$className];
        }

        $r = array('f' => HOOKS_DIR, 'm' => WEBAPP_MODULES_DIR);
        $path = explode('_', $className);

        // replace by folder
        if(in_array($path[0], array_keys($r))) $path[0] = $r[$path[0]];

        // get last path
        $lastPath = lcfirst(end($path)).DS;

        // get file name
        $fileName = lcfirst(array_pop($path)).'.'.$ext;        

        // set path in string
        $fpath = join(DS, $path).DS;

        // file is in the right folder corresponding to the name
        if(is_file($fpath.$fileName))
        {
            self::$_cache[$className] = $fpath.$fileName;
            if($mustCache) file_put_contents($cache, serialize(self::$_cache), LOCK_EX);
            return $fpath.$fileName;
        }
        
        // file is in the framework
        if($className[0] === 'f')
        {            
            $possibilities = array();
            
            // hooks path
            $possibilities[] = $fpath.'core'.DS.$fileName; // f_Session => /hooks/core/session.class.php
            $possibilities[] = $fpath.$lastPath.$fileName; // f_Session => /hooks/session/session.class.php
            
            // original path
            $path[0] = FW_DIR; $fpath = join(DS, $path).DS;            
            $possibilities[] = $fpath.$fileName;           // f_Session => /oxygen/session.class.php
            $possibilities[] = $fpath.'core'.DS.$fileName; // f_Session => /oxygen/core/session.class.php
            $possibilities[] = $fpath.$lastPath.$fileName; // f_Session => /oxygen/session/session.class.php

            // testing possibilities
            foreach($possibilities as $possibility)
            {                
                if(is_file($possibility))
                {
                    self::$_cache[$className] = $possibility;
                    if($mustCache) file_put_contents($cache, serialize(self::$_cache), LOCK_EX);
                    return $possibility;
                }
            }
        }

        // file is module in webapp
        if($className[0] === 'm')
        {
            $path[0] = MODULES_DIR; $fpath = join(DS, $path).DS;            
            if(is_file($fpath.$fileName))
            {
                self::$_cache[$className] = $fpath.$fileName;
                if($mustCache) file_put_contents($cache, serialize(self::$_cache), LOCK_EX);
                return $fpath.$fileName;
            }
        }       

        // returns false if file not found
        return false;
    }
}