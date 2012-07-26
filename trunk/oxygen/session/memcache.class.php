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

class f_session_Memcache implements f_session_Interface
{
    protected $_savePath;
    protected $_sessionName;
    
    private $_memcache;
    
    public function __construct($lifetime)
    {
        $this->_lifeTime = $lifetime;
        
        if(!extension_loaded('memcache') && !extension_loaded('memcached')) 
           throw new Exception('Memcache(d) extension is not loaded');     
        
        $memcache = extension_loaded('memcache') ? new Memcache() : new Memcached();
        
        $host = Config::get('session.memcache_host', '127.0.0.1');
        $port = Config::get('session.memcache_port', 11211);
        
        $memcache->addServer($host, $port);
        
        $stats = extension_loaded('memcache') ? $memcache->getExtendedStats() : $memcache->getStats();
        $available = (bool) $stats["$host:$port"];    
        
        $this->_memcache = $memcache->connect($host, $port);
        
        if(!$available || !$this->_memcache) throw new Exception('Cannot connect to memcache(d) server');        
    }

    public function open($savePath, $sessionName)
    {
        return true;
    }

    public function close()
    {
        return true;
    }

    public function read($id)
    {
        return $this->_memcache->get("sessions/{$id}");
    }

    public function write($id, $data)
    {
        return $this->_memcache->set("sessions/{$id}", $data, MEMCACHE_COMPRESSED, $this->_lifeTime);
    }

    public function destroy($id)
    {
        return $this->_memcache->delete("sessions/{$id}");
    }

    public function gc($lifetime)
    {
        return true;
    }        
}