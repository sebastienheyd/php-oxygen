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
        
        if(time() > $cache[0])
		{
			unlink($file);
			return false;
		}
        
        $datas = $cache[2];        
        if($cache[1] === 'array' || $cache[1] === 'object') $datas = unserialize($datas);
        
        $this->_cacheArr[$id] = $datas;
        
        return $datas;
    }
    
    public function save($id, $datas, $ttl = 60)
    {         
        $this->_cacheArr[$id] = $datas; 
        
        $type = gettype($datas);
        
        if($type === 'NULL' || $type === 'resource') $datas = false;        
        if($type === 'array' || $type === 'object') $datas = serialize($datas);
                
        return file_put_contents($this->_cachePath.DS.$id.'.cache', serialize(array(time() + $ttl, $type, $datas)), LOCK_EX) !== false;
    }
    
    public function delete($id)
    {        
        return @unlink($this->_cachePath.DS.$id.'.cache');
    }
    
    public function flush()
    {        
        $files = array_map('unlink', glob(Search::file('*.cache')->in($this->_cachePath)->fetch()));
        return !in_array(false, $files);
    }
    
    public function isSupported()
    {
        if(!is_dir($this->_cachePath)) mkdir($this->_cachePath, 0775, true);
        if(!is_writable($this->_cachePath)) throw new Exception('Cache dir '.$this->_cachePath.' is not writeable');        
    }   
}