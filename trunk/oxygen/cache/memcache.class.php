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

class f_cache_Memcache implements f_cache_Interface
{
    private static $_instance;
    
    private $_memcache;
    
    /**
     * @return f_cache_Memcache 
     */
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
    
    public function isSupported()
    {
        if(!extension_loaded('memcache') && !extension_loaded('memcached')) 
           throw new Exception('Memcache(d) extension is not loaded');     
        
        $memcache = extension_loaded('memcache') ? new Memcache() : new Memcached();
        
        $host = Config::get('cache.memcache_host', '127.0.0.1');
        $port = Config::get('cache.memcache_port', 11211);
        
        $memcache->addServer($host, $port);
        
        $stats = extension_loaded('memcache') ? $memcache->getExtendedStats() : $memcache->getStats();
        $available = (bool) $stats["$host:$port"];    
        
        $this->_memcache = $memcache->connect($host, $port);
        
        if(!$available || !$this->_memcache) throw new Exception('Cannot connect to memcache(d) server');
    }
}