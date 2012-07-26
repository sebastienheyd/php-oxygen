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

class f_cache_File implements f_cache_Interface
{
    static $_instance;
    
    private $_cachePath;
    private $_cacheArr = array();
    private $_timeToken;
    
    /**
     * @return f_cache_File 
     */
    public static function getInstance()
    {
        if(!isset(self::$_instance)) self::$_instance = new self();
        return self::$_instance;   
    }
    
    private function __construct()
    {
        $this->_cachePath = CACHE_DIR.DS.'files';    
        $this->isSupported();

    }
    
    public function get($id)
    {        
        if(isset($this->_cacheArr[$id])) return $this->_cacheArr[$id];
        
        $file = $this->_cachePath.DS.$id.'.cache';
        
        if(!is_file($file)) return false;
        
        $cache = unserialize(file_get_contents($file));
        
        if(time() > ($cache[0] + $cache[1]) || $cache[0] <= $this->_getTimeToken())
		{
			unlink($file);
			return false;
		}
        
        $data = $cache[3];        
        if($cache[2] === 'array' || $cache[2] === 'object') $data = unserialize($data);
        
        $this->_cacheArr[$id] = $data;
        
        return $data;
    }
    
    public function save($id, $data, $ttl = 60)
    {         
        $this->_cacheArr[$id] = $data; 
        
        $type = gettype($data);
        
        if($type === 'NULL' || $type === 'resource') $data = false;        
        if($type === 'array' || $type === 'object') $data = serialize($data);
                
        return file_put_contents($this->_cachePath.DS.$id.'.cache', serialize(array(time(), $ttl, $type, $data)), LOCK_EX) !== false;
    }
    
    public function delete($id)
    {        
        return @unlink($this->_cachePath.DS.$id.'.cache');
    }
    
    public function flush()
    {        
        $this->_timeToken = time();        
        return file_put_contents($this->_cachePath.DS.'time_token', $this->_timeToken, LOCK_EX) !== false;
    }
    
    public function isSupported()
    {
        if(!is_dir($this->_cachePath)) mkdir($this->_cachePath, 0775, true);
        if(!is_writable($this->_cachePath)) throw new Exception('Cache dir '.$this->_cachePath.' is not writeable');        
    }   
    
    private function _getTimeToken()
    {
        if($this->_timeToken !== null) return $this->_timeToken;
        if(is_file($this->_cachePath.DS.'time_token')) return file_get_contents($this->_cachePath.DS.'time_token');
        if($this->flush()) return $this->_timeToken;      
        trigger_error('Cannot define cache time token', E_USER_ERROR);
    }
}