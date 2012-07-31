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

class f_log_Stream implements f_log_Interface
{
    private static $_instance;
    
    public static function getInstance()
    {
        if(!isset(self::$_instance)) self::$_instance = new self();
        return self::$_instance;   
    }
    
    public function write($type, $msg)
    {
        if(mb_strlen($type) > 3) $type = substr($type, 0, 3);
        $type = strtoupper($type);
        file_put_contents('php://output', '<pre>'.$type.' | '.$msg.'</pre>'.PHP_EOL);
    }
}