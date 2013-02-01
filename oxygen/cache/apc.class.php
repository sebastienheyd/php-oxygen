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

class f_cache_Apc implements f_cache_Interface
{
    static $_instance;
    
    public function __construct()
    {
        $this->isSupported();                
    }
    
    public function get($id)
    {
        return apc_fetch($id);
    }
    
    public function save($id, $data, $ttl = 60)
    {
        return apc_store($id, $data, $ttl);
    }
    
    public function delete($id)
    {
        return apc_delete($id);
    }
    
    public function flush()
    {
        return apc_clear_cache('user');
    }
    
    public function isSupported()
    {
        if(!extension_loaded('apc') || !function_exists('apc_store')) throw new Exception('APC extension is not loaded');
    }
}