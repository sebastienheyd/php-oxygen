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

abstract class Action
{
	private $_view;
    public $cacheId;
    public $cacheLifetime;
	private $_model;

    /**
     * Magic method to call non existent class methods
     * 
     * @param string $method    Method name
     * @param type $args        Method arguments
     * @return mixed            Return the method result if exist else throw an error
     */
    public function __call($method, $args)
    {
        switch ($method)
        {
            // return models
            case 'getModel':
                return $this->_model;
            break;
        
            // alias of setModel()
            case 'addToModel':
                $this->setModel($args[0], $args[1]);
            break;
        
            // alias of t()
            case 'translate':
                $this->t($args[0], $args[1], $args[2], $args[3]);
            break;
        
            // return view current content
            case 'getView':
                return $this->_view;
            break;
        
            default:
                throw new BadMethodCallException('Method '.$method.' does not exist');
            break;
        }
    }
    
    /**
     * Magic method to get non existant class property value
     * 
     * @param string $name      Name of the property to get
     * @return mixed            Return the property value
     */  
    public function __get($name)
    {
        switch ($name)
        {
            case 'request':
                return Request::getInstance();
            break;
        }
    }
    
    public function hasCache($viewName, $cacheId)
    {
        preg_match('/^m_(.*)_action_(.*)/', get_class($this), $matches);
        
        $module = $matches[1];    
        $l = explode('_', $matches[2]);
        $filename = lcfirst(end($l)).ucfirst($viewName).'.html';

        if($file = get_module_file($module, 'template'.DS.$filename))
        {            
            return Template::getInstance($file, $module)->hasCache($cacheId);           
        }
        
        return false;
    }

	/**
	 * Set the view name to display
	 *
	 * @param string $value             Name of the view to display
     * @param string $cacheId           [optional] Cache indentifier to use 
     * @param string $cacheLifetime     [optional] Cache lifetime 
     * @return void
	 */
	public function setView($viewName, $cacheId = null, $cacheLifetime = 3600)
	{
		$this->_view = $viewName;
        $this->cacheId = $cacheId;
        $this->cacheLifetime = $cacheLifetime;
	}
    
	/**
	 * Set a model to pass to view
	 *
	 * @param string $name      Name of the model
	 * @param mixed $value      Value of the model
	 * @return Action           Current instance of Action
	 */
	public function setModel($name, $value)
	{
		$this->_model[$name] = $value;
		return $this;
	}
    
    /**
     * Translate the given string to the current i18n locale language.
     * 
     * @param type $string      The string to translate
     * @param type $args        [optional] Associative array of elements to replace in the given string.<br />Exemple : translate('My name is %name%', array('name' => 'Jim'))
     * @param type $domain      [optional] Name of the category of message (the xml file to get, ex : errors => errors.fr.xml). Default is 'messages'
     * @param type $srcLang     [optional] ISO 639-1 code of the source language. Default is null (will take the default lang)
     * @return string           The translated string if found, else the source string
     */
    public function t($string, $args = array(), $domain = 'messages', $srcLang = null)
    {
        if($srcLang === null) $srcLang = i18n::getDefaultLang();
        
        $class = get_class($this);
        preg_match('/^m_(.*)_action/i', $class, $m);        
        
        // Get locale file with full locale code (ex : fr_CA)
        $file = get_module_file($m[1], 'i18n'.DS.$domain.'.'.I18n::getLocale().'.xml');        
        if($file !== false) return I18n::t($file, $string, $args, $srcLang, $class);
        
        // Get locale file with only lang code (ex : fr)
        $file = get_module_file($m[1], 'i18n'.DS.$domain.'.'.I18n::getLang().'.xml', false);
        return I18n::t($file, $string, $args, $srcLang, $class);        
    }
}