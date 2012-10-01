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
    private $_error;
    private $_size;
    
    const UPLOAD_SUCCESS = 1;
    const FILE_EXISTS = 10;
    const FILE_IS_NOT_UPLOADED = 20;
    const MOVE_UPLOAD_ERROR = 30;
    
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
            if(is_uploaded_file($_FILES[$match[1]]['tmp_name'][$match[2]]))
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
            if(is_uploaded_file($_FILES[$name]['tmp_name']))
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
     * @return string
     */
    public function getName()
    {
        return $this->_name;
    }
    
    /**
     * Check if file is correctly uploaded
     * 
     * @return boolean 
     */
    public function isUploaded()
    {
        return !empty($this->_tmp_name);
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
     * Check if uploaded file is an image
     * 
     * @return boolean
     */    
    public function isImage()
    {
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
        if($checkIfFileExists)
        {
            if(is_file($file)) return self::FILE_EXISTS;
        }
        
        if($this->isUploaded())
        {
            if(@move_uploaded_file($this->_tmp_name, $file)) return self::UPLOAD_SUCCESS;
            return self::MOVE_UPLOAD_ERROR;
        }
        return self::FILE_IS_NOT_UPLOADED;
    }
}