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

define(SMARTY_SPL_AUTOLOAD, 1);
define(PS, PATH_SEPARATOR);

// include paths
set_include_path(get_include_path().PS.
                 FW_DIR.DS.'core'.PS.
                 FW_DIR.DS.'lib'.PS.
                 FW_DIR.DS.'lib'.DS.'vendor'.DS.'smarty'.DS.'sysplugins'.PS.
                 PROJECT_DIR.DS.'model');

// define file extensions to get
spl_autoload_extensions('.class.php,.php');

// first try to load from included paths
spl_autoload_register();

// or load from autoload method
spl_autoload_register('autoload');

/**
 * Magic __autoload method
 * See : http://www.php.net/autoload
 * 
 * @param string $class_name    The class name to get
 */
function autoload($className)
{
    if($path = class_file_path($className)) require_once($path);
}