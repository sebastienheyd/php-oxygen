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

abstract class View
{
	private $_model;
    private $_module;

	abstract public function execute();

    /**
     * Magic method to get non existant class property value
     * 
     * @param string $name      Name of the property to get
     * @return mixed            Return the property value
     */    
    public function __get($name)
    {
        return isset($this->$name) ? $this->$name : null;
    }

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
            case 'setModel':
                $this->_model = $args[0];
            break;
        
            case 'setModule':
                $this->_module = $args[0];
            break;
        
            default:
                throw new BadMethodCallException('Method '.$method.' does not exist');
            break;        
        }
    }

	/**
     * Get the model value
     * 
	 * @return mixed    Return the model value
	 */
	public function getModel($name)
	{
		return isset($this->_model[$name]) ? $this->_model[$name] : null;
	}

    /**
     * Set the template to get/render
     * 
     * @param string $template      Template file name
     * @return Template             Return an instance of Template
     */
    public function setTemplate($template)
    {
        $tpl = Template::getInstance();

        // Assign all models to template
        if(is_array($this->_model) && !empty($this->_model))
	    {
            foreach($this->_model as $k => $v)
            {
                $tpl->assign($k, $v);
            }
	    }

        $tpl->addTemplateDir(WEBAPP_MODULES_DIR.DS.$this->_module.DS.'template');
        $tpl->addTemplateDir(MODULES_DIR.DS.$this->_module.DS.'template');

        $tpl->setTemplate($template);

        return $tpl;
    }
}