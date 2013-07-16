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

class Upload
{
    private static $_instances;
    
    private $_name;
    private $_type;
    private $_tmp_name;
    private $_error = 4;
    private $_size;
    
    private $_extensions = array();
    private $_max_size;
    private $_check_image = false;
    
    const UPLOAD_ERR_FILE_EXISTS = 9;
    const UPLOAD_ERR_FILE_NOT_IMAGE = 10;
    const UPLOAD_ERR_FILE_EXTENSION = 11;
    
    /**
     * Main constructor
     * 
     * @param string $name      Name of the field to get
     * @return Upload 
     */
    private function __construct($name)
    {
        if(preg_match('#(.*?)\[(.*?)\]#i', $name, $match))
        {            
            if(isset($_FILES[$match[1]]['tmp_name'][$match[2]]))
            {
                $fields = array('name', 'type', 'tmp_name', 'error', 'size');
                foreach($fields as $field)
                {
                    $prop = '_'.$field;
                    $this->$prop = $_FILES[$match[1]][$field][$match[2]];
                }
            }
        }
        else
        {
            if(isset($_FILES[$name]['tmp_name']))
            {
                foreach($_FILES[$name] as $k => $v)
                {
                    $prop = '_'.$k;
                    $this->$prop = $v;
                }                
            }            
        }
    }        
      
    /**
     * Get instance of uploaded file
     * 
     * @param string $name      Name of the field to get
     * @return Upload 
     */
    public static function get($name)
    {
        $k = md5($name);
        if(!isset(self::$_instances[$k]))  self::$_instances[$k] = new self($name);
        return self::$_instances[$k];
    }
    
    /**
     * Return the uploaded file temp file name
     *
     * @return string
     */
    public function getTempName()
    {
        return $this->_tmp_name;
    }
    
    /**
     * Return the original file name
     * 
     * @param boolean $withExtension    [optional] Return with extension or not
     * @return string
     */
    public function getName($withExtension = true)
    {
        if(!$withExtension) return substr($this->_name, 0, strrpos($this->_name, '.'));
        return $this->_name;
    }
    
    
    /**
     * Return the original file extension
     *
     * @return string
     */
    public function getExtension()
    {
        return substr($this->_name, strrpos($this->_name, '.') + 1);
    }    
    
    /**
     * Check if file is correctly uploaded
     * 
     * @return boolean 
     */
    public function isUploaded()
    {
        return $this->_error === UPLOAD_ERR_OK;
    }    
    
    /**
     * Return upload error
     * 
     * @return int
     */
    public function getError()
    {
        return $this->_error;
    }
    
    public function filterExtensions($extensions)
    {
        $extensions = func_get_args();                        
        $extensions = explode(',', join(',', $extensions)); 
        $extensions = array_map('trim', $extensions);
        
        $this->_extensions = $extensions;
        return $this;
    }
    
    public function maxSize($size)
    {
        $this->_max_size = $size;
        return $this;
    }
    
    /**
     * Check if uploaded file has the given extension(s)
     * 
     * @param mixed $extensions     Array or comma separated list
     * @return boolean
     */
    public function hasExtension($extensions)
    {        
        if($this->isUploaded())
        {
            $extensions = func_get_args();                        
            $extensions = explode(',', join(',', $extensions)); 
            $extensions = array_map('trim', $extensions);
            $regexp = '#(\.'.join('|\.', $extensions).')$#i';           
            return preg_match($regexp, $this->_name);
        }
        return false;
    }
    
    
    /**
     * Check if uploaded file has weight less than given size
     * 
     * @param int $size     Size in octets
     * @return boolean
     */
    public function sizeIsLessThan($size)
    {
        return $this->_size <= $size;
    }    
    
    /**
     * Check if uploaded file is an image
     * 
     * @return boolean
     */    
    public function isImage()
    {
        $this->_check_image = true;
        return $this;
        return getimagesize($this->_tmp_name) !== false;
    }
    
    /**
     * Save uploaded file to destination file
     * 
     * @param string $file                  Destination file path
     * @param boolean $checkIfFileExists    [optional] If true, return Upload::$FILE_EXISTS error code if file exists. Default is false
     * @return integer                      Return an error code
     */
    public function saveTo($file, $checkIfFileExists = false)
    {        
        if(!$this->isUploaded()) return false;
        
        if($checkIfFileExists)
        {
            if(is_file($file)) $this->_error = self::UPLOAD_ERR_FILE_EXISTS;
            return false;
        }
        
        if($this->_check_image && getimagesize($this->_tmp_name) === false)
        {
            $this->_error = self::UPLOAD_ERR_FILE_NOT_IMAGE;
            return false;
        }   
        
        if(!empty($this->_extensions))
        {
            $regexp = '#(\.'.join('|\.', $this->_extensions).')$#i';           
            if(!preg_match($regexp, $this->_name))
            {
                $this->_error = self::UPLOAD_ERR_FILE_EXTENSION;
                return false;
            }
        }

        if(@move_uploaded_file($this->_tmp_name, $file)) return true;
        
        $this->_error = UPLOAD_ERR_CANT_WRITE;
        return false;
    }
}