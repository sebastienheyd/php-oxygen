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

class Uri
{
    private static $_instances;

    private $_uri;
    private $_segments;
    private $_suffix;
    private $_prefix;
    private $_defined;
    private $_host;
    private $_origin;

    /**
     * Get class instance
     * 
     * @return Uri      Return Uri instance (singleton)
     */
    public static function getInstance($uri = null)
    {
        $instanceId = $uri;
        if($uri === null) $instanceId = 'default';
        if(!isset(self::$_instances[$instanceId])) self::$_instances[$instanceId] = new self($uri);
        return self::$_instances[$instanceId];
    }

    /**
     * Constructor
     * 
     * @return void
     */
    private function  __construct($uri = null)
    {
        $this->setUri($uri);
    }
    
    /**
     * @return string
     */
    public function __toString()
    {
        return $this->_origin;
    }
    
    /**
     * Define uri to use and parse
     * 
     * @param string $uri   [optional] Uri string or null. If null will parse from REQUEST_URI
     * @return Uri
     */
    public function setUri($uri = null)
    {
        $this->_uri = $uri === null ? $this->_parseUri() : $uri;
        $this->_segments = $this->_uri != '/' ? explode('/', trim($this->_uri, '/')) : array();
        $this->_host = $_SERVER['HTTP_HOST'];
        $this->_defined = $uri !== null;
        if(!$this->_defined) $this->_origin = $this->_uri;
        return $this;
    }
    
    /**
     * Is the current uri explicitly defined by setUri ? (is current uri rerouted ?)
     * 
     * @return boolean      Return true if uri is explicitly defined
     */
    public function isDefined()
    {
        return $this->_defined;
    }    
    
    /**
     * Parse uri from REQUEST_URI
     * 
     * @return string   Return the Uri string
     */
    private function _parseUri()
    {
        $uri = '';

        // For IIS...
        if(!isset($_SERVER['REQUEST_URI'])) trigger_error('REQUEST_URI is not defined', E_USER_ERROR);
        
        // get from SCRIPT_NAME
        $uri = $_SERVER['REQUEST_URI'];

        // prevent calling index.php when routed only
        if(Config::get('route.routed_only') == '1' && preg_match('#^\/index.php#i', $uri)) Error::show404();
        
        // remove script name
        if (strpos($uri, $_SERVER['SCRIPT_NAME']) === 0)  $uri = substr($uri, strlen($_SERVER['SCRIPT_NAME']));
        
        // remove suffix from config
        $this->_suffix = HTTP_SUFFIX;
        if($this->_suffix !== null)  $uri = str_replace($this->_suffix, '', $uri);
        
        // remove prefix from config
        $this->_prefix = HTTP_PREFIX;
        if($this->_prefix !== null)  $uri = str_replace($this->_prefix, '', $uri);

        // split on ? to get QUERY_STRING
        $p = preg_split('#\?#i', $uri, 2);
        $uri = $p[0];

        // remove last slash
        $uri = rtrim($uri, '/');

        // Feed QUERY_STRING and put into $_GET
        $_GET = array();
        if(isset($p[1]))
        {
            $_SERVER['QUERY_STRING'] = $p[1];
            parse_str($_SERVER['QUERY_STRING'], $_GET);
        }

        if(empty($uri)) $uri = '/';

        return $uri;
    }

    /**
     * Get a segment from the current URI
     * 
     * @param integer $n        Position of the segment in the uri
     * @param mixed $default    [optional] Default value if segment is not found. Default value is null
     * @return string           Part of the uri as a string
     */
    public function segment($n, $default = null)
    {
        return isset($this->_segments[$n-1]) ? $this->_segments[$n-1] : $default;
    }
    
    /**
     * Return all segments
     * 
     * @return array
     */
    public function getSegments()
    {
        return $this->_segments;
    }

    /**
     * Return a slice of uri segments
     * 
     * @param integer $offset   Start point of the array slice
     * @param integer $length   [optional] Length of the portion to extract, set it to null for no limitation. Default is null
     * @return array            Sequence of the uri parts as an array
     */
    public function segmentsSlice($offset, $length = null)
    {
        return array_slice($this->_segments, $offset - 1, $length);
    }        
    
    /**
     * Get a substring of uri by uri segment
     * 
     * @param integer $offset   Position in segments where to start
     * @param integer $length   Length of portion to extract
     * @return string           Portion of uri 
     */
    public function substr($offset, $length = null)
    {
        return join('/', array_slice($this->_segments, $offset, $length));
    }        

    /**
     * Returns the number of segments
     * 
     * @return integer      The number of segments from the uri
     */
    public function nbSegments()
    {
        return count($this->_segments);
    }
    
    /**
     * Returns the last segment value
     * 
     * @return string       The value of the last segment from the uri
     */
    public function lastSegment()
    {
        return end($this->_segments);
    }

    /**
     * Get the current uri
     * 
     * @param boolean $origin   If uri is explicitly defined return origin uri. If true uri is current uri, not the rewrited one.
     * @return string           The uri as a string
     */
    public function getUri($origin = true)
    {
        if($origin && $this->_origin !== null) return $this->_origin; 
        return $this->_uri;
    }
    
    /**
     * Get the current host
     * 
     * @return string       The current host as a string
     */
    public function getHost($httpPrefix = false)
    {
        $host = '';
        
        if($httpPrefix) $host .= (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != '' && $_SERVER['HTTPS'] != 'off') ? 'https://' : 'http://';
        
        $host .= $this->_host;
        
        return $host;
    }    
}