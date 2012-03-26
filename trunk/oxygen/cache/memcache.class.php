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

class f_cache_Memcache extends f_cache_Driver
{
    static $_instance;
    
    private $_memcache;
    
    public static function getInstance()
    {
        if(!isset(self::$_instance)) self::$_instance = new self();
        return self::$_instance;   
    }
    
    private function __construct()
    {
        $this->isSupported();        
        $memcache = new Memcache();
        $memcache->connect(Config::get('cache', 'memcache_host', '127.0.0.1'), Config::get('cache', 'memcache_port', 11211));     
        $this->_memcache = $memcache;
    }
    
    public function get($id)
    {
        return $this->_memcache->get($id);
    }
    
    public function save($id, $datas, $ttl = 60)
    {
        return $this->_memcache->set($id, $datas, MEMCACHE_COMPRESSED, $ttl);
    }
    
    public function delete($id)
    {
        return $this->_memcache->delete($id);
    }
    
    public function flush()
    {
        return $this->_memcache->flush();
    }
    
    protected function isSupported()
    {
        if(!extension_loaded('memcache')) trigger_error('Memcached extension is not loaded', E_USER_ERROR);
    }
}