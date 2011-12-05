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

class UserAgent
{    
    private static $instance;
    
    private $agent;
    private $languages = array();
    private $charsets = array();
    
    /**
     * Return singleton instance
     * 
     * @return UserAgent
     */
    public static function getInstance()
    {
        if(!isset(self::$instance))
        {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    protected function __construct()
	{
		if(isset($_SERVER['HTTP_USER_AGENT']))
		{
			$this->agent = trim($_SERVER['HTTP_USER_AGENT']);
		}
	}
    
    // -------------------------------------------- GENERAL / DETECTION
    
    public function getAgentString()
    {
        return $this->agent;
    }
    
    // -------------------------------------------- LANGUAGES
    
    /**
     * Get the accepted languages
     * 
     * @return array 
     */
    public function getLanguages()
    {
        return empty($this->languages) ? $this->_setLanguages() : $this->languages;
    }
    
    /**
     * Get the first accepted language
     * 
     * @return string       The first language in ISO 639-1 format else "undefined"
     */
    public function getLanguage()
    {
        if(empty($this->languages)) $this->_setLanguages();        
        return preg_replace('/-(.*)$/i', '', first($this->languages));
    }
    
    /**
     * Check if the browser accepts the given language
     * 
     * @param string $language      ISO 639-1 or ISO 639-1 with ISO3166-1, ex : "fr", "fr_FR", "fr-FR", "fr-fr"
     * @return boolean              Return true if language is supported by the browser
     */
    public function acceptLanguage($language)
    {
        $language = strtolower(str_replace('_', '-', $language));
        if(empty($this->languages)) $this->_setLanguages();
        return in_array($language, $this->languages);
    }
    
    // -------------------------------------------- CHARSETS
    
    /**
     * Get the accepted charsets
     * 
     * @return array 
     */
    public function getCharsets()
    {
        return empty($this->charsets) ? $this->_setCharsets() : $this->charsets;
    }
    
    /**
     * Get the first accepted charset
     * 
     * @return string 
     */
    public function getCharset()
    {
        return first(empty($this->charsets) ? $this->_setCharsets() : $this->charsets);
    }
    
    /**
     * Check if the browser accpets the given charset
     * 
     * @param string $charset       The charset to test
     */
    public function acceptCharset($charset)
    {
        if(empty($this->charsets)) $this->_setCharsets();
        return in_array($charset, $this->charsets);
    }        
    
    // -------------------------------------------- REFERRER
    
    /**
     * Get the referrer
     * 
     * @return string 
     */
    public function getReferrer()
    {
        return (!isset($_SERVER['HTTP_REFERER']) || $_SERVER['HTTP_REFERER'] == '') ? '' : trim($_SERVER['HTTP_REFERER']);
    }
    
    /**
     * Is this a referral from another site ?
     * 
     * @return boolean 
     */
    public function isReferral()
    {
        return (!isset($_SERVER['HTTP_REFERER']) || $_SERVER['HTTP_REFERER'] == '') ? false : true;
    }
    
    // -------------------------------------------- PRIVATE METHODS
    
    /**
     * Set the browser accepted languages to private var
     * 
     * @return array        Array of accepted languages
     */
    private function _setLanguages()
    {
        if((empty($this->languages)) && isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) && $_SERVER['HTTP_ACCEPT_LANGUAGE'] != '')
		{
			$languages = preg_replace('/(;q=[0-9\.]+)/i', '', strtolower(trim($_SERVER['HTTP_ACCEPT_LANGUAGE'])));
			$this->languages = explode(',', $languages);            
		}

		if(empty($this->languages))
		{
			$this->languages = array('undefined');
		}
        
        return $this->languages;
    }
    
    /**
     * Set the browser accepted charsets to private var
     * 
     * @return array        Array of accepted charsets
     */
    private function _setCharsets()
    {
        if (empty($this->charsets) && isset($_SERVER['HTTP_ACCEPT_CHARSET']) && $_SERVER['HTTP_ACCEPT_CHARSET'] != '')
		{
			$charsets = preg_replace('/(;q=.+)/i', '', strtolower(trim($_SERVER['HTTP_ACCEPT_CHARSET'])));
			$this->charsets = explode(',', $charsets);
		}

		if(empty($this->charsets))
		{
			$this->charsets = array('undefined');
		}
        
        return $this->charsets;
    }
}