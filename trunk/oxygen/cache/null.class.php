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

class f_cache_Null extends f_cache_Driver
{
    static $_instance;
    
    public static function getInstance()
    {
        if(self::$_instance === null) self::$_instance = new self();
        return self::$_instance;   
    }
    
    public function get($id)
    {
        return false;
    }
    
    public function save($id, $datas, $ttl = 60)
    {
        return true;
    }
    
    public function delete($id)
    {
        return true;
    }
    
    public function flush()
    {
        return true;
    }
    
    protected function isSupported()
    {
        return true;
    }
}