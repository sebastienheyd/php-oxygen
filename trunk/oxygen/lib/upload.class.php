<?php

/**
 * This file is part of the PHP Oxygen package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @copyright   Copyright (c) 2011-2014 Sébastien HEYD <sheyd@php-oxygen.com>
 * @author      Sébastien HEYD <sheyd@php-oxygen.com>
 * @package     PHP Oxygen
 */

class Upload
{    
    private $_config = array();
    private $_name;
    private $_type;
    private $_tmp_name;
    private $_error;
    private $_size;
    
    public $errors = array();
    public $saved_to;
    public $saved_as;
    
    const UPLOAD_ERR_MAX_SIZE = 10;
    const UPLOAD_ERR_FILE_EXTENSION = 20;
    const UPLOAD_ERR_FILE_TYPE = 30;            
    const UPLOAD_ERR_FILE_NOT_IMAGE = 40;
    const UPLOAD_ERR_MOVE_FAILED = 50;
    const UPLOAD_ERR_OVERWRITE = 60;
    
    /**
     * Main constructor
     * 
     * @param string $name      Name of the form field to get
     * @return Upload 
     */
    private function __construct($name)
    {
        if(preg_match('#(.*?)\[(.*?)\]#i', $name, $match))
        {            
            $fields = array('name', 'type', 'tmp_name', 'error', 'size');
            foreach($fields as $field) $this->{"_$field"} = $_FILES[$match[1]][$field][$match[2]];
        }
        else
        {
            foreach($_FILES[$name] as $k => $v) $this->{"_$k"} = $v;                   
        }
        
        if($this->_error !== UPLOAD_ERR_OK) $this->errors[] = $this->_getErrorMessage($this->_error);
        
        unset($this->_error);
    }        
      
    /**
     * Get instance of Upload
     * 
     * @param string $name      Name of the form field to get
     * @return Upload|false     Return instance or false if there is no file upload
     */
    public static function get($name)
    {
        if(empty($_FILES)) return false;
        
        if(preg_match('#(.*?)\[(.*?)\]#i', $name, $match))
        {            
            if(isset($_FILES[$match[1]]['error'][$match[2]]) &&
               $_FILES[$match[1]]['error'][$match[2]] == UPLOAD_ERR_NO_FILE) return false;
        }
        else
        {
            if(isset($_FILES[$name]) && $_FILES[$name]['error'] == UPLOAD_ERR_NO_FILE) return false;           
        }
        
        $k = md5($name);
        return new self($name);
    }    
        
    /**
     * Return the original uploaded file name
     * 
     * @param boolean $withExtension    [optional] If true, return fully qualified. Default is true.
     * @return string
     */
    public function getOriginalName($withExtension = true)
    {
        return $this->_getFileName($this->_name, $withExtension);
    }    
    
    /**
     * Return the original uploaded file extension
     *
     * @return string
     */
    public function getOriginalExtension()
    {
        return $this->_getFileExtension($this->_name);
    }    
    
    /**
     * Fully qualified path where the uploaded file was saved 
     *  
     * @return string
     */
    public function getSaveTo()
    {
        return $this->saved_to;
    }
    
    /**
     * Name of the file that was saved
     * 
     * @return string
     */
    public function getSaveAs()
    {
        return $this->saved_as;
    }
        
    /**
     * Return the errors array
     * 
     * @return array
     */
    public function getErrors()
    {
        return $this->errors;
    }
    
    /**
     * Define configuration settings
     * 
     * @param array $config     Configuration array, see documentation for parameters
     */
    public function config(array $config)
    {
        $this->_config = $config;
        return $this;
    }
    
    /**
     * Save uploaded file to destination file
     * 
     * @param string $file                  Destination file
     * @return boolean                      Return true if file saving is complete
     */
    public function saveTo($file)
    {        
        if(!empty($this->_config))
        {
            foreach($this->_config as $filter => $value)
            {
                $method =  '_'.ucfirst(preg_replace('/(?:^|_)(.?)/e', "strtoupper('$1')", $filter));
                if(method_exists($this, $method)) $this->$method($value);                                    
            }
        }
        
        if(isset($this->_config['normalize']) && $this->_config['normalize'] === true)
        {
            $dir = dirname($file);
            $file = $dir.DS.String::toUrl($this->_getFileName($file, false)).'.'.$this->_getFileExtension($file);
        }        
        
        if(!isset($this->_config['overwrite']) || $this->_config['overwrite'] === false)
        {
            if(is_file($file))
            {
                if(isset($this->_config['auto_rename']) && $this->_config['auto_rename'] === true)
                {
                    $dir = realpath(dirname($file));
                    $files = glob($dir.DS.$this->_getFileName($file, false).'-*.'.$this->_getFileExtension($file));
                    $file = $dir.DS.$this->_getFileName($file, false).'-'.(count($files)+1).'.'.$this->_getFileExtension($file);
                }
                else
                {
                    $this->errors[] = $this->_getErrorMessage(self::UPLOAD_ERR_OVERWRITE);
                }
            }
        }        

        if(!empty($this->errors)) return false;        

        if(!@move_uploaded_file($this->_tmp_name, $file)) 
        {
            $this->errors[] = $this->_getErrorMessage(self::UPLOAD_ERR_MOVE_FAILED);
            return false;
        }     
        
        $this->saved_to = realpath($file);
        $this->saved_as = basename($file);
        
        return true;
    }       
    
    /**
     * Check allowed file extensions
     * 
     * @param string $extensions    Comma separated list
     * @return void
     */
    private function _ExtWhitelist($extensions)
    {
        $extensions = $this->_parseList($extensions);
        $regexp = '#(\.'.join('|\.', $extensions).')$#i';           
        if(!preg_match($regexp, $this->_name)) 
                $this->errors[] = $this->_getErrorMessage(self::UPLOAD_ERR_FILE_EXTENSION);
    }
    
    /**
     * Check disallowed file extensions
     * 
     * @param string $extensions    Comma separated list
     * @return void
     */
    private function _ExtBlacklist($extensions)
    {
        $extensions = $this->_parseList($extensions);
        $regexp = '#(\.'.join('|\.', $extensions).')$#i';           
        if(preg_match($regexp, $this->_name)) 
                $this->errors[] = $this->_getErrorMessage(self::UPLOAD_ERR_FILE_EXTENSION);
    }
    
    /**
     * Check allowed mimetype
     * 
     * @param type $types   Comma separated list
     * @return void
     */
    private function _TypeWhitelist($types)
    {
        $types = $this->_parseList($types);
        list($type, $sub) = explode('/', $this->_type);
        if(!in_array($type, $types)) 
                $this->errors[] = $this->_getErrorMessage(self::UPLOAD_ERR_FILE_TYPE);
    }
    
    /**
     * Check disallowed mimetype
     * 
     * @param type $types   Comma separated list
     * @return void
     */
    private function _TypeBlacklist($types)
    {
        $types = $this->_parseList($types);
        list($type, $sub) = explode('/', $this->_type);
        if(in_array($type, $types)) 
                $this->errors[] = $this->_getErrorMessage(self::UPLOAD_ERR_FILE_TYPE);
    }
    
    /**
     * Check allowed mimetype
     * 
     * @param type $types   Comma separated list
     * @return void
     */
    private function _MimeWhitelist($types)
    {
        $types = $this->_parseList($types);
        if(!in_array($this->_type, $types)) 
                $this->errors[] = $this->_getErrorMessage(self::UPLOAD_ERR_FILE_TYPE);
    }
    
    /**
     * Check disallowed mimetype
     * 
     * @param type $types   Comma separated list
     * @return void
     */
    private function _MimeBlacklist($types)
    {
        $types = $this->_parseList($types);
        if(in_array($this->_type, $types)) 
                $this->errors[] = $this->_getErrorMessage(self::UPLOAD_ERR_FILE_TYPE);
    }
    
    /**
     * Check maximum size
     * 
     * @param integer $size     Size in bytes
     * @return void
     */
    private function _MaxSize($size)
    {
        if($this->_size > $size) 
            $this->errors[] = $this->_getErrorMessage(self::UPLOAD_ERR_MAX_SIZE);
    }
    
    /**
     * Check if file is an image
     * 
     * @return void
     */    
    private function _IsImage()
    {
        if(function_exists('exif_imagetype'))
        {
            if(exif_imagetype($this->_tmp_name) === false) 
                $this->errors[] = $this->_getErrorMessage(self::UPLOAD_ERR_FILE_NOT_IMAGE);
        }
        else
        {
            if(getimagesize($this->_tmp_name) === false) 
                $this->errors[] = $this->_getErrorMessage(self::UPLOAD_ERR_FILE_NOT_IMAGE);
        }
    }
    
    /**
     * Define if file must be overwrited
     * 
     * @param boolean $value
     * @return void
     */
    private function _Overwrite($value)
    {
        $this->_config['overwrite'] = $value;
    }
    
    /**
     * Parse a comma/space/pipe/dotcomma separated list
     * 
     * @param string $list
     * @return type
     */
    private function _parseList($list)
    {
        $list = func_get_args();                        
        $list = preg_split('#[\s,|;]+#', join(',', $list)); 
        $list = array_map('trim', $list);
        $list = array_unique($list);
        return $list;
    }   
    
    /**
     * Get filename from a fully qualified path
     * 
     * @param string $file              Absolute path or filename
     * @param boolean $withExtension    [optional] If true, return with extension. Default is true
     * @return string
     */
    private function _getFileName($file, $withExtension = true)
    {
        $file = basename($file);
        if(!$withExtension) return mb_substr($file, 0, mb_strrpos($file, '.', 0, 'utf-8'), 'utf-8');
        return $file;
    }
    
    /**
     * Get extension from a fully qualified path
     * 
     * @param string $file              Absolute path or filename
     * @return string
     */
    private function _getFileExtension($file)
    {
        $file = basename($file);
        return mb_strtolower(mb_substr($file, mb_strrpos($file, '.', 0, 'utf-8') + 1, 15, 'utf-8'));
    }
    
    /**
     * Return code and message from a given error code
     * 
     * @param integer $code
     * @return array            Array containing the error code and the error text
     */
    private function _getErrorMessage($code)
    {
        $codes = array(
            UPLOAD_ERR_OK           => 'There is no error, the file uploaded with success',
            UPLOAD_ERR_INI_SIZE     => 'The uploaded file exceeds the upload_max_filesize directive in php.ini',
            UPLOAD_ERR_FORM_SIZE    => 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form',
            UPLOAD_ERR_PARTIAL      => 'The uploaded file was only partially uploaded',
            UPLOAD_ERR_NO_FILE      => 'No file was uploaded',
            UPLOAD_ERR_NO_TMP_DIR   => 'Missing a temporary folder',
            UPLOAD_ERR_CANT_WRITE   => 'Failed to write file to disk',
            UPLOAD_ERR_EXTENSION    => 'A PHP extension stopped the file upload',
            self::UPLOAD_ERR_MAX_SIZE       => 'The file uploaded exceeds the maximum file size defined in the configuration',
            self::UPLOAD_ERR_FILE_EXTENSION => 'The extension of the file uploaded is blacklisted or not whitelisted',
            self::UPLOAD_ERR_FILE_TYPE      => 'The type of the file uploaded is blacklisted or not whitelisted',
            self::UPLOAD_ERR_FILE_NOT_IMAGE => 'The file uploaded is not an image',
            self::UPLOAD_ERR_MOVE_FAILED    => 'The uploaded filename could not be moved from temporary storage to the path specified',
            self::UPLOAD_ERR_OVERWRITE      => 'The uploaded filename could not be saved because a file with that name already exists'        
        );
        
        return array('code' => $code, 'message' => $codes[$code]);
    }
}