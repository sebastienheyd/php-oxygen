<?php

class Asset
{   
    // Authorized assets extensions and mime types
    private static $_mimes = array('css' => 'text/css', 'js' => 'text/javascript', 'less' => 'text/css');
    
    // Default options, can be overridde by options in configuration file
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
    public static function getInstance()
    {
        return new self();
    }
    
    /**
     * Constructor
     */
    private function __construct()
    {                   
        $options = to_array(Config::get('asset'));
        $this->_options = array_merge($this->_options, $options);
    }
    
    /**
     * Add an asset to the collection
     * 
     * @param string $file      Asset URI to add
     * @return Asset
     */
    public function add($file)
    {    
        $f = array();
        
        // get file extension
        $f['uri'] = $file;  
        $f['ext'] = substr($file, strrpos($file, '.')+1);  
        $f['basename'] = ltrim(substr($file, 0, strrpos($file, '.')), '/');         
        
        // verify if file extension is authorized
        if(!in_array($f['ext'], array_keys(self::$_mimes))) 
                trigger_error('Filetype not supported '.$f['ext'], E_USER_ERROR);
        
        // get mime type
        $mime = self::$_mimes[$f['ext']];
        if(isset($this->_mime) && $this->_mime != $mime) 
            trigger_error('Filetype not matching', E_USER_ERROR);
        
        $this->_mime = $mime;
        
        // get file path
        if(($f['path'] = $this->_getFilePath($file)) === false)
        {
            $cache = $this->_getCacheFilePath($f['basename']);
            if(is_file($cache))
            {
                $this->_cacheFile = $cache;  
                $this->_timestamp = filemtime($cache);
                return;
            }
            trigger_error('Asset file not found !', E_USER_ERROR);
        }
        else
        {
            // get timestamp
            $ftime = $f['ext'] === 'less' ? $this->_compileLess($f['path']) : filemtime($f['path']);        
            $this->_timestamp = max(array($this->_timestamp, $ftime));           
            $this->_files[] = $f;     
        }    
        
        return $this;
    }   
    
    /**
     * Return the timestamp of the last modified element
     * 
     * @return integer
     */
    public function getLastModified()
    {
        return $this->_timestamp;
    }
    
    public function getMimeType()
    {
        return $this->_mime;
    }
    
    public function getUid()
    {
        return md5(serialize($this->_files).$this->_timestamp);
    }

    /**
     * Output asset collection to the browser
     */
    public function output()
    {   
        $content = $this->compile();

        while (ob_get_level()) { ob_end_clean(); }
        
        header("Content-Type: ".$this->_mime);
        
        if(($gzip = $this->_checkGzip()) !== false)
        {
            header("Vary: Accept-Encoding");       
            header("Content-Encoding: $gzip");
        }
        
        if($this->_options['cache'] === true || $this->_options['cache'] === '1')
        {
            $gmDate = gmdate('D, d M Y H:i:s', $this->_timestamp).' GMT';
            
            header("Pragma: public");           
            header("Cache-Control: public");           
            header("Expires: " . gmdate('D, d M Y H:i:s', time()+$this->_options['expires']).' GMT');
            
            if(isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) && $_SERVER['HTTP_IF_MODIFIED_SINCE'] === $gmDate)
            {
                header("Last-Modified: $gmDate", true, 304);
                exit(1);
            }        
        
            header("Last-Modified: $gmDate", true, 200);            
        }
        
        echo $content;
    }
    
    /**
     * Compile the asset collection and store in a cache file
     * 
     * @return string       Content of the cached file
     */
    public function compile()
    {
        $cache = $this->_getCacheFilePath();
        if(is_file($cache)) return file_get_contents($cache);      

        $content = '';

        foreach($this->_files as $file)
        {
            if($file['ext'] === 'less')
            {
                $tmp = $this->_getLess($file['path']);
            }
            else
            {
                $tmp = file_get_contents($file['path']);
            }
            
            if($file['ext'] === 'less' || $file['ext'] === 'css')
            {
                
                $tmp = $this->fixRelativePaths($tmp, $file['uri']);
            }
            
            $content .= $tmp.PHP_EOL;
        }        

        if($this->_options['minify'] == true)
        {
            if($this->_mime === 'text/css')
            {  
                require_once(FW_DIR.DS."lib".DS.'vendor'.DS."minify".DS."cssmin.php");        
                $compressor = new CSSmin();
                $content = $compressor->run($content);
            }
            else
            {
                require_once(FW_DIR.DS."lib".DS.'vendor'.DS."minify".DS."jsminplus.php"); 
                $content = JSMinPlus::minify($content);
            }
        }        
        
        if($content === '') return '';
        
        $content = $this->_checkGzip() ? gzencode($content) : $content;
        
        file_put_contents($cache, $content);
        touch($cache, $this->_timestamp);
        
        return $content;
    }   
    
    /**
     * Convert relative to absolute path in CSS content
     * 
     * @param string $css               CSS content to fix
     * @param string $absolutePath      Absolute path
     * @return string
     */
    public function fixRelativePaths($css, $uri)
    {
        $absolutePath = dirname($uri).'/';
        $search = '#url\((?!\s*[\'"]?(?:https?:)?/)\s*([\'"])?#i';
        $replace = "url($1{$absolutePath}$2";
        return preg_replace($search, $replace, $css);
    }
    
    /**
     * Clear all assets caches
     * @return void
     */
    public function clearCache()
    {
        $files = Search::file('*.*')->in(CACHE_DIR.DS.'assets')->fetch();
        if(empty($files)) return;
        foreach($files as $file) unlink($file);
    }

    /**
     * Get the cache file path of the compiled collection
     * 
     * @return string
     */
    private function _getCacheFilePath($uid = null)
    {        
        if($uid === null) $uid = $this->getUid();
        if(isset($this->_cacheFile)) return $this->_cacheFile;
        $this->_cacheFile = CACHE_DIR.DS.'assets'.DS.$uid;
        if($this->_options['minify'] == true) $this->_cacheFile .= '.min';
        $this->_cacheFile .= '.'.array_search($this->_mime, self::$_mimes);
        if($this->_checkGzip()) $this->_cacheFile .= '.gz';
        return $this->_cacheFile;
    }
    
    /**
     * Compile LESS file to CSS and store in cache if necessary
     * 
     * @param string $file      LESS file to compile
     * @return integer          Timestamp of the last compilation
     */
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
    
    /**
     * Get compiled LESS file from cache
     * 
     * @param string $file      LESS file to get content from
     * @return string           Compiled CSS
     */
    private function _getLess($file)
    {
        $cacheFile = CACHE_DIR.DS.'assets'.DS.md5($file).".less.cache"; 
        $c = unserialize(file_get_contents($cacheFile));
        return $c['compiled'];
    }    
    
    /**
     * Get physical file path from the asset's uri
     * 
     * @param type $uri             Asset URI
     * @return string|boolean       Return file path or false if file is not found
     */
    private function _getFilePath($uri)
    {
        // get uri segments
        $segments = explode('/', ltrim($uri, '/'));
        $mUri = join('/', array_slice($segments, 1));

        // paths to check in order
        $paths = array(
            WWW_DIR.$uri,
            FW_DIR . DS . 'assets' . $uri,
            WEBAPP_MODULES_DIR . DS . $segments[0] . DS . 'assets' . DS . $mUri,
            MODULES_DIR . DS . $segments[0] . DS . 'assets' . DS . $mUri
        );

        // if found, return
        foreach($paths as $p) if(is_file($p) && is_readable($p)) return $p;
        
        return false;
    }
    
    /**
     * Check if gzip is active on the server and if the browser support it
     * 
     * @return boolean
     */
    private function _checkGzip()
    {
        if($this->_options['gzip'] != true) return false;
        
        if(isset($this->_gzip)) return $this->_gzip;
        
        $this->_gzip = false;
        
        // check for gzip compression
        if (isset($_SERVER['HTTP_ACCEPT_ENCODING']))
            $encodings = explode(',', strtolower(preg_replace("/\s+/", "", $_SERVER['HTTP_ACCEPT_ENCODING'])));  
        
        if((in_array('gzip', $encodings) || in_array('x-gzip', $encodings) || isset($_SERVER['---------------'])) && function_exists('gzencode') && !ini_get('zlib.output_compression'))
            $this->_gzip = in_array('x-gzip', $encodings) ? "x-gzip" : "gzip";
        
        return $this->_gzip;
    }    
}