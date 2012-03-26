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

class f_cache_File extends f_cache_Driver
{
    static $_instance;
    
    private $_cachePath;
    
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
        $file = $this->_cachePath.DS.$id.'.cache';
        
        if(!is_file($file)) return false;
        
        $cache = unserialize(file_get_contents($file));
        
        if(($cache['time'] + $cache['ttl']) < time())
		{
			unlink($file);
			return false;
		}
        
        $datas = $cache['datas'];
        
        if($cache['type'] == 'array' || $cache['type'] == 'object')
        {
            $datas = unserialize($cache['datas']);
        }
        
        return $datas;
    }
    
    public function save($id, $datas, $ttl = 60)
    {                        		
        return file_put_contents($this->_cachePath.DS.$id.'.cache', $this->_prepareDatas($datas, $ttl), LOCK_EX) !== false;
    }
    
    public function delete($id)
    {
        return @unlink($this->_cachePath.DS.$id.'.cache');
    }
    
    public function flush()
    {
        $files = Search::file('*.cache')->in($this->_cachePath)->fetch();
        
        $i = 0;
        foreach($files as $file)
        {
            if(unlink($file)) $i++;
        }
        
        return count($files) == $i;
    }
    
    protected function isSupported()
    {
        if(!is_dir($this->_cachePath))
        {
            mkdir($this->_cachePath, 0775, true);
        }            
            
        if(!is_writable($this->_cachePath)) trigger_error('Cache dir '.$this->_cachePath.' is not writeable', E_USER_ERROR);        
    }
    
    private function _prepareDatas($datas, $ttl)
    {        
        $type = gettype($datas);
        
        if($type == 'NULL' || $type == 'resource') return false;
        
        if($type == 'array' || $type == 'object') $datas = serialize($datas);

        $contents = array(
				'time'		=> time(),
				'ttl'		=> $ttl,	
                'type'      => $type,
				'datas'		=> $datas
        );
        
        return serialize($contents);
    }    
}