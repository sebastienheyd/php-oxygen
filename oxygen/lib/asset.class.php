<?php

class Asset
{   
    private static $_mimes = array('css' => 'text/css', 'js' => 'text/javascript', 'less' => 'text/css');
    
    private $_options = array('cache' => true, 'minify' => true, 'expires' => 31536000, 'gzip' => true);    
    private $_files = array();    
    private $_mime;
    private $_gzip;
    private $_timestamp = 0;
    private $_cacheFile;
    
    /**
     * 
     * @return Asset
     */
    public static function getInstance($options = array())
    {
        return new self($options);
    }
    
    private function __construct($options)
    {                   
        $this->_options = array_merge($this->_options, $options);
    }
    
    public function add($file)
    {    
        $f = array();
        
        // get file extension
        $f['ext'] = substr($file, strrpos($file, '.')+1);  
        
        // verify if file extension is authorized
        if(!in_array($f['ext'], array_keys(self::$_mimes))) 
                trigger_error('Filetype not supported', E_USER_ERROR);
        
        // get file path
        if(($f['path'] = $this->_getFilePath($file)) === false)
                Error::show404();
        
        $ftime = $f['ext'] === 'less' ? $this->_compileLess($f['path']) : filemtime($f['path']);        
        $this->_timestamp = max(array($this->_timestamp, $ftime));

        // get mime type
        $mime = self::$_mimes[$f['ext']];
        if(isset($this->_mime) && $this->_mime != $mime) 
            trigger_error('Filetype not matching', E_USER_ERROR);
        
        $this->_mime = $mime;
        
        $this->_files[] = $f;     
    }
    
    private function _compile()
    {
        $cache = $this->_getCacheFilePath();        
        if(is_file($cache) && filemtime($cache) === $this->_timestamp) return file_get_contents($cache);

        $content = '';
        
        foreach($this->_files as $file)
        {
            if($file['ext'] === 'less')
            {
                $content .= $this->_getLess($file['path']);
            }
            else
            {
                $content .= file_get_contents($file['path']);
            }
        }        
        
        if($this->_options['minify'] === true)
        {
            if($this->_mime === 'text/css')
            {  
                require_once(FW_DIR.DS."lib".DS.'vendor'.DS."minify".DS."cssmin.php");        
                $content = CssMin::minify($content, array('ConvertLevel3Properties' => false, 'Variables' => false));
            }
            else
            {
                require_once(FW_DIR.DS."lib".DS.'vendor'.DS."minify".DS."jsminplus.php"); 
                $content = JSMinPlus::minify($content);
            }
        }        
        
        $content = $this->_checkGzip() ? gzencode($content) : $content;
        
        file_put_contents($cache, $content);
        touch($cache, $this->_timestamp);
        
        return $content;
    }
    
    private function _getCacheFilePath()
    {        
        if(isset($this->_cacheFile)) return $this->_cacheFile;
        $this->_cacheFile = CACHE_DIR.DS.'assets'.DS.md5(serialize($this->_files)).'.'.array_search($this->_mime, self::$_mimes);        
        if($this->_checkGzip()) $this->_cacheFile .= '.gz';
        return $this->_cacheFile;
    }
    
    private function _compileLess($file)
    {
        $cacheFile = CACHE_DIR.DS.'assets'.DS.md5($file).".less.cache";        
        $cache = is_file($cacheFile) ? unserialize(file_get_contents($cacheFile)) : $file;                

        require_once(FW_DIR.DS."lib".DS.'vendor'.DS."lessphp".DS."lessc.inc.php");         
        $less = new lessc;                
        $less->addImportDir(dirname($file));                        
        $compiled = $less->cachedCompile($cache);
        
        if (!is_array($cache) || $compiled["updated"] > $cache["updated"]) 
        {
            file_put_contents($cacheFile, serialize($compiled));   
            return $compiled['updated'];
        }
        
        return $cache['updated'];
    }
    
    private function _getLess($file)
    {
        $cacheFile = CACHE_DIR.DS.'assets'.DS.md5($file).".less.cache"; 
        $c = unserialize(file_get_contents($cacheFile));
        return $c['compiled'];
    }
    
    public function output()
    {
        while (ob_get_level()) { ob_end_clean(); }    
        
        header("Content-Type: ".$this->_mime);
        
        if(($gzip = $this->_checkGzip()) !== false)
        {
            header("Vary: Accept-Encoding");       
            header("Content-Encoding: $gzip");
        }
        
        if($this->_options['cache'] === true)
        {
            $gmDate = gmdate('D, d M Y H:i:s', $this->_timestamp).' GMT';
            
            header("Cache-Control: maxage=".$this->_options['expires'].', must-revalidate');           
            header("Expires: " . gmdate('D, d M Y H:i:s', time()+$this->_options['expires']).' GMT');
            
            if(isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) && $_SERVER['HTTP_IF_MODIFIED_SINCE'] === $gmDate)
            {
                header("Last-Modified: $gmDate", true, 304);
                exit(1);
            }        
        
            header("Last-Modified: $gmDate", true, 200);            
        }
        
        echo $this->_compile();
    }


    private function _getFilePath($file)
    {
        // get uri segments
        $segments = explode('/', ltrim($file, '/'));
        $mUri = join('/', array_slice($segments, 1));

        // paths to check in order
        $paths = array(
            WWW_DIR.$file,
            FW_DIR . DS . 'assets' . $file,
            WEBAPP_MODULES_DIR . DS . $segments[0] . DS . 'assets' . DS . $mUri,
            MODULES_DIR . DS . $segments[0] . DS . 'assets' . DS . $mUri
        );
        
        // if found, return
        foreach($paths as $p) if(is_file($p) && is_readable($p)) return $p;
        
        return false;
    }
    
    private function _checkGzip()
    {
        if($this->_options['gzip'] === false) return false;
        
        if(isset($this->_gzip)) return $this->_gzip;
        
        $this->_gzip = false;
        
        // check for gzip compression
        if (isset($_SERVER['HTTP_ACCEPT_ENCODING']))
            $encodings = explode(',', strtolower(preg_replace("/\s+/", "", $_SERVER['HTTP_ACCEPT_ENCODING'])));  
        
        if((in_array('gzip', $encodings) || in_array('x-gzip', $encodings) || isset($_SERVER['---------------'])) && function_exists('gzencode') && !ini_get('zlib.output_compression'))
        {
            $this->_gzip = in_array('x-gzip', $encodings) ? "x-gzip" : "gzip";
        }
        
        return $this->_gzip;
    }    
}