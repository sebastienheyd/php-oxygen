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

class f_log_Null implements f_log_Interface
{
    private static $_instance;
    
    /**
     * @return f_log_Null 
     */
    public static function getInstance()
    {
        if(!isset(self::$_instance)) self::$_instance = new self();
        return self::$_instance;  
    }
    
    public function write($type, $msg)
    {
        return true;
    }
}