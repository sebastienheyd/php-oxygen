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

require_once(FW_DIR.DS."lib".DS.'vendor'.DS."smarty".DS."Smarty.class.php");

class Template extends Smarty
{
    private $_templateFile;

    /**
     * Get a new instance of Template (smarty)
     * 
     * @param string $template      [optional] Path to a template file. Default is null
     * @param string $module        [optional] Name of a module to add as template dir
     * 
     * @return Template             Return instance of Template
     */    
    public function  __construct($template = null, $module = null)
    {
        parent::__construct();
        
        $this->cache_dir = WEBAPP_DIR.DS.'cache'.DS.'html'.DS;
        $this->compile_dir = WEBAPP_DIR.DS.'cache'.DS.'templates_c'.DS;
        
        $this->addPluginsDir(HOOKS_DIR.DS.'lib'.DS.'smartyplugins');
        $this->addPluginsDir(FW_DIR.DS.'lib'.DS.'smartyplugins'); 
        
        if(!is_null($module))
        {
            $this->addTemplateDir(HOOKS_DIR.DS.$module.DS.'templates');
            $this->addTemplateDir(WEBAPP_MODULES_DIR.DS.$module.DS.'templates');            
        }
        
        if(!is_null($template))
        {
            $this->setTemplate($template);
        }   
    }

    /**
     * Get a new instance of Template
     * 
     * @param string $template      [optional] Path to a template file. Default is null
     * @param string $module        [optional] Name of a module to add as template dir
     * 
     * @return Template             Return instance of Template
     */
    public static function getInstance($template = null, $module = null)
    {
        return new self($template, $module);
    }

	/**
	 * Set other delimiter in smarty
     *
     * @param string $left      Left delimiter to use in templates
     * @param string $right     Right delimiter to use in templates
	 * @return Template
	 */
    public function setDelimiter($left, $right)
    {
        $this->left_delimiter = $left;
        $this->right_delimiter = $right;
        return $this;
    }

	/**
	 * Set the template file to use
     *
	 * @param string $templateFile      Template full pathname
	 * @return Template                 Return current instance of Template
	 */
	public function setTemplate($templateFile)
	{        
        $this->_templateFile = $templateFile;
		return $this;
	}
    
    /**
     * Check if a cache exists for this template and eventualy the given cacheId
     * 
     * @param string $cacheId   [optional] A cache identifier.
     * @return boolean          If true cache exists 
     */
    public function hasCache($cacheId = null)
    {
        $this->setCaching(Smarty::CACHING_LIFETIME_SAVED);
        return $this->isCached($this->_templateFile, $cacheId);
    }    

    /**
     * Return compiled template content to get into a variable.
     * 
     * @param string $cacheId       [optional] A cache identifier. If null cache is not activated for current instance. Default is null
     * @return string               The compiled template content
     */
    public function get($cacheId = null)
    {
        $this->assign('HTTP_PREFIX', HTTP_PREFIX);
        if(!is_null($cacheId)) $this->setCaching(Smarty::CACHING_LIFETIME_SAVED);
        return $this->fetch($this->_templateFile, $cacheId);
    }    
    
    /**
     * Return compiled template content to the navigator
     * 
     * @param string $cacheId       [optional] A cache identifier. If null cache is not activated for current instance. Default is null
     * @return void                 Return nothing, send the content to the navigator
     */    
    public function render($cacheId = null)
    {
        $this->assign('HTTP_PREFIX', HTTP_PREFIX);
        if(!is_null($cacheId)) $this->setCaching(Smarty::CACHING_LIFETIME_SAVED);
        $this->display($this->_templateFile, $cacheId);
    }
}