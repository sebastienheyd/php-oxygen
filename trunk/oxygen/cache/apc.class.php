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

class f_cache_Apc extends f_cache_Driver
{
    static $_instance;
    
    public static function getInstance()
    {
        if(!isset(self::$_instance)) self::$_instance = new self();
        return self::$_instance;   
    }
    
    private function __construct()
    {
        $this->isSupported();                
    }
    
    public function get($id)
    {
        return apc_fetch($id);
    }
    
    public function save($id, $datas, $ttl = 60)
    {
        return apc_store($id, $datas, $ttl);
    }
    
    public function delete($id)
    {
        return apc_delete($id);
    }
    
    public function flush()
    {
        return apc_clear_cache('user');
    }
    
    protected function isSupported()
    {
        if(!extension_loaded('apc') || !function_exists('apc_store'))
            trigger_error('APC extension is not loaded', E_USER_ERROR);
    }
}