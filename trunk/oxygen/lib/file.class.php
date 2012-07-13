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

class File
{
    private $_file;
    private $_dirname;
    private $_basename;
    private $_extension;
    
    const SIZE_IN_BYTES = 1;
    const SIZE_IN_OCTETS = 2;
        
    private static $_instances;        
    
    /**
     * Get a new instance of file (multiton)
     * 
     * @param   string      $file   Full path to a file in string
     * @return  File|false          Return false if file is not found
     */
    public static function load($file)
    {        
        if(!isset(self::$_instances[$file]))
        {
            if(!is_file($file) || !is_readable($file)) return false;
            self::$_instances[$file] = new self($file);
        }
        return self::$_instances[$file];
    }
    
    /**
     * Constructor
     * 
     * @param string    $file   Full path to a file
     */
    private function __construct($file)
    {
        $this->_file = $file;
        
        $infos = pathinfo($file);

		$infos['_dirname'] = realpath($infos['dirname']);

		foreach ($infos as $key => $info)
		{
			$this->{'_'.$key} = $info;
		}
    }     
    
    /**
     * Returns the file name
     * 
     * @param boolean $withExtension    [optional] Return file name with extension, default is true
     * @return string                   Return the filename (with or without extension)
     */
    public function getFileName($withExtension = true)
    {
        return $withExtension ? $this->_filename.'.'.$this->_extension : $this->_filename;
    }
    
    /**
     * Returns the mimetype base from the file extension<br />Take mimetypes from mimetypes.xml
     * 
     * @param string $extOrFileName     The extension or filename
     * @return string                   The mimetype
     */
    public static function getMimeTypeFrom($extOrFileName)
    {
        $pos = strrpos($extOrFileName, '.');
        $ext = $pos !== false ? substr($extOrFileName, $pos+1) : $extOrFileName;
        
        $xml = simplexml_load_file(dirname(__FILE__).DS.'xml'.DS.'mimetypes.xml');

        /* @var $xml SimpleXMLElement */
        $res = $xml->xpath('mime[@ext="'.$ext.'"]');
        
        if(isset($res[0])) return (string) $res[0];
        
        return 'text/plain';
    }
    
    /**
     * Returns the mime type of the file
     * 
     * @return string   Mime-type of the file
     */
    public function getMimeType()
    {
        return self::getMimeTypeFrom($this->_extension);
    }
    
    /**
     * Is current file an image ?
     * 
     * @return boolean      If is true, file is an image
     */
    public function isImage()
    {
        return getimagesize($this->_file) !== false;
    }
    
    /**
     * Returns the file size in bytes
     * @return integer
     */
    public function getSize()
    {              
        return filesize($this->_file);
    }
    
    /**
     * Returns the file size formatted in B, MB, GB, etc...
     * 
     * @param boolean $sizeInOctet  [optionnal] If true, display size in octets instead of bytes. Default is false
     * @return string 
     */
    public function getFormattedSize($sizeInOctet = false)
    {
        return self::formatSize(filesize($this->_file), $sizeInOctet);
    }
    
    /**
     * Format bytes value to KB, MB, etc...
     * 
     * @param integer $bytes        Bytes value
     * @param boolean $sizeInOctet  [optionnal] If true, display size in octets instead of bytes. Default is false
     * @return string 
     */
    public static function formatSize($bytes, $sizeInOctet = false)
    {
        $symbols = array('B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB');        
        if($sizeInOctet === true) $symbols = array('o', 'Ko', 'Mo', 'Go', 'To', 'Po', 'Eo', 'Zo', 'Yo');
      
        $exp = $bytes > 0 ? floor(log($bytes) / log(1024)) : 0;

        return sprintf('%.2f '.$symbols[$exp], ($bytes/pow(1024, floor($exp))));
    }
    
    /**
     * Returns content of the loaded file
     * 
     * @param string $restrictToDir     [optional] Check if file is in this directory and / or in a subdirectory of it. Default is APP_DIR
     * @return string   File content
     */
    public function getContents($restrictToDir = APP_DIR)
    {
        $this->isInDir($restrictToDir);
        return file_get_contents($this->_file);
    }
    
    /**
     * Outputs the file to the navigator
     * 
     * @param string $restrictToDir     [optional] Check if file is in this directory and / or in a subdirectory of it. Default is APP_DIR
     * @param boolean $useCache         [optional] True to use minimized cached files for js and css
     * @return void                     Return void but output the file content to the navigator
     */
    public function output($restrictToDir = APP_DIR, $useCache = true)
    {
        $this->isInDir($restrictToDir);
                
        // Clean up the output buffer
        while (ob_get_level()) { ob_end_clean(); }               

        $format = 'D, d M Y H:i:s \G\M\T';
        $lastModified = filemtime($this->_file);
        $gmDate = gmdate($format, $lastModified);
        $expires = 60*60*24*365; // one year
        
        // Set the file header and read the file
        header("Pragma: public");
        header("Vary: Accept-Encoding");       
        header("Cache-Control: maxage=".$expires);
        header("content-type: ".$this->getMimeType());              
        header("Expires: " . gmdate($format, time()+$expires));              

        if(isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) && $_SERVER['HTTP_IF_MODIFIED_SINCE'] === $gmDate)
        {
            header("Last-Modified: $gmDate", true, 304);
            exit();
        }        
        
        header("Last-Modified: $gmDate", true, 200);
        
        // special case on js and css files to minify them
        if($this->_extension === 'js' || $this->_extension === 'css')
        {         
            if($gzip = $this->_checkGzip()) header("Content-Encoding: " . $gzip);                        
            echo $this->getMinify($lastModified, $useCache);
        }
        else
        {
            readfile($this->_file);    
        }
                
        // Exit the script, you cannot display anything after that
        exit();
    }
    
    public function getMinify($lastModified, $useCache = true)
    {
        $gzip = $this->_checkGzip();
        
        // if we want and we can compress
        if($useCache && $gzip) 
        {            
            // save into a cache file
            $cacheFile = CACHE_DIR.DS.'assets'.DS.md5($this->_file).'.'.$this->_extension.'.gz';

            // there is no compressed cache file or cache file has not the same modification time
            if(!is_file($cacheFile) || (is_file($cacheFile) && $lastModified != filemtime($cacheFile)))
            {
                // generate a new cache file
                if(!is_dir(CACHE_DIR.DS.'assets')) mkdir(CACHE_DIR.DS.'assets');
                if($this->_extension === 'css')
                {
                    $content = $this->_minifyCss(file_get_contents($this->_file));   
                }
                else
                {
                    $content = $this->_minifyJs(file_get_contents($this->_file));   
                }
                file_put_contents($cacheFile, gzencode($content));
                touch($cacheFile, $lastModified);
            }

            return file_get_contents($cacheFile);
        }        
        else
        {
            // no gzip compression...
            if($this->_extension === 'css') return $this->_minifyCss(file_get_contents($this->_file));             
            return $this->_minifyJs(file_get_contents($this->_file)); 
        }
    }
    
    private function _checkGzip()
    {
        // check for gzip compression
        if (isset($_SERVER['HTTP_ACCEPT_ENCODING']))
            $encodings = explode(',', strtolower(preg_replace("/\s+/", "", $_SERVER['HTTP_ACCEPT_ENCODING'])));  
        
        if((in_array('gzip', $encodings) || in_array('x-gzip', $encodings) || isset($_SERVER['---------------'])) && function_exists('gzencode') && !ini_get('zlib.output_compression'))
        {
            return in_array('x-gzip', $encodings) ? "x-gzip" : "gzip";
        }
        
        return false;
    }
    
    /**
     * Minify javascript when a js file is outputed
     * 
     * @param string $content   Js file content
     * @return string           The minified javascript
     */
    private function _minifyJs($content)
    {
        require_once(FW_DIR.DS."lib".DS.'vendor'.DS."minify".DS."jsminplus.php"); 
        return JSMinPlus::minify($content);
    }
    
    /**
     * Minify css when a css file is outputed
     * 
     * @param string $content   Css file content
     * @return string           The minified css
     */    
    private function _minifyCss($content)
    {
        require_once(FW_DIR.DS."lib".DS.'vendor'.DS."minify".DS."cssmin.php");        
        return CssMin::minify($content, array('ConvertLevel3Properties' => true));
    }
    
    /**
     * Checks if file is authorized to process (checks if file is in project directory).
     * 
     * @param string $dir   [optional] Check if file is in this directory and / or in a subdirectory of it. Default is APP_DIR
     * @param type $error   [optional] Throw an error if true, default is true
     * @return boolean      Return true if current file is in the given directory (or in subdirectory of it)
     */
    public function isInDir($dir = APP_DIR, $error = true)
    {
        if($dir != '/' && !strstr($this->_dirname, $dir)) 
        {
            if($error) trigger_error('Security error : file is out of base directory', E_USER_ERROR);
            return false;
        }
        
        return true;
    }
    
    /**
     * Force the file to download
     * 
     * @param string $filename      [optional] Name of the file when downloading, default is current instanciated file name
     * @param string $restrictToDir [optional] Check if file is in this directory and / or in a subdirectory of it. Default is APP_DIR
     * @return void                 Return nothing but send the file to the navigator and force the download
     */
	public function forceDownload($filename = null, $restrictToDir = APP_DIR)
	{
        if(!strstr($this->_dirname, $restrictToDir)) trigger_error('Security error : file is out of base directory', E_USER_ERROR);
        
        // Clean up the output buffer
        while (ob_get_level()) ob_end_clean();
        
        // Sets name and size
		$original = $this->_basename;
        $filesize = filesize($this->_file);        
        if($filename === null) $filename = $original;

        // Set headers
		header('Content-Type: application/force-download; name="'.$original.'"');
		header("Content-Length: ".$filesize);
		header("Content-Disposition: attachment; filename=\"".$filename."\"");

        // Read the file
		readfile($this->_file);
        exit();
	}
}
