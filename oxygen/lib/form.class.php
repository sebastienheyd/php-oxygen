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

class Form
{
    private $_post;
    private $_errors = array();
    private $_rules = array();
    
    /**
     * Get a new instance of Form
     * 
     * @param Document $object      [optionnal] an instance of an object extending Document
     * @return Form
     */
    public static function getInstance($object = null)
    {
        if($object === null)
        {
            $post = Request::getInstance()->post;
        }
        else
        {
            if($object instanceof Document) $post = $object->getObjectVars();
        }
        
        if(!isset($post) || empty($post)) return false;
        
        return new self($post);
    }
    
    /**
     * Main contructor
     * 
     * @param array $post   Posted vars or an array of values from a Document object
     */
    private function __construct($post)
    {
        $this->_post = $post;
    }
    
    /**
     * Posted value check method, use this method to check if a value match given rule(s)
     * 
     * @param string $field     Posted field name
     * @param string $rule      Rule(s) to check, see oxygen/form/check for available checkers
     * @param string $fullName  [optionnal] Field name in full text, for errors reporting
     * @return Form             Current instance of Form
     */
    public function check($fieldName, $rules, $fullName = null)
    {
        $rules = explode('|', $rules);
        
        foreach($rules as $rule)
        {
            $args = null;
            
            // get rule argument if needed
            if(preg_match('/([a-z0-9\-_]*?)\[(.*)\]/i', $rule, $matches))
            {
                $rule = $matches[1];
                $args = $matches[2];
            }

            // field is not required and value is empty
            if($rule !== 'required' && $this->_post->$fieldName === '') continue;
            
            // get class name
            $className = 'f_form_check_'.ucfirst($rule);
            if(array_key_exists($rule, $this->_rules)) $className = $this->_rules[$rule];           

            // class instanciation
            $rule = new $className($this->_post, $fieldName, $fullName);

            // rule execution
            if(!$rule->check($args))
            {            
                if(!isset($this->_errors[$fieldName])) $this->_errors[$fieldName] = $rule->getError();
            }            
        }
                
        return $this;
    }
    
    /**
     * Add a user specified rule for checking
     * 
     * @param string $ruleName      The rule name
     * @param string $className     The class name to use (must extends f_form_check_Abstract to work)
     */
    public function addRule($ruleName, $className)
    {
        if(!class_exists($className)) trigger_error('Class '.$className.' not found', E_USER_ERROR);
        $this->_rules[$ruleName] = $className;
    }
    
    /**
     * Is posted form valid ?
     * 
     * @return boolean      Return true if form is valid
     */
    public function isValid()
    {
        return empty($this->_errors);
    }
    
    /**
     * Return form errors if any
     * 
     * @param string $ucfirst   Must the method returns errors with first char uppercased ?
     * @return array            An array of errors (or an empty array) 
     */
    public function getErrors($ucfirst = true)
    {
        if($ucfirst) $this->_errors = array_map('ucfirst', $this->_errors);
        return $this->_errors;
    }
    
    /**
     * Get form values as an array
     * 
     * @return array
     */
    public function getValues($asArray = false)
    {
        if(get_magic_quotes_gpc()) $this->_post = to_object(array_map('stripslashes', to_array($this->_post)));        
        if($asArray) return to_array ($this->_post);       
        return $this->_post;
    }
    
    /**
     * Get a field value
     * 
     * @param string $fieldName     The field name to get the value from
     * @param mixed $default        Default value if field is not found
     * @return mixed                The field value
     */
    public function getValue($fieldName, $default = null)
    {
        $values = $this->getValues();        
        return isset($values->$fieldName) ?  $values->$fieldName : $default;
    }
    
    /**
     * Return true if a checkbox is checked
     * 
     * @param string $fieldName     The checkbox name
     * @return boolean              Return true the checkbox is checked
     */
    public function isChecked($checkBoxName)
    {
        $values = $this->getValues();
        return isset($values->$checkBoxName);
    }
    
    /**
     * Set an object properties by his class name, the object must extends Document
     * 
     * @param string $className     The class name of the object to set properties
     * @return Document             The object with setted properties
     */
    public function setObjPropertiesByClassName($className)
    {
        $obj = call_user_func(array($className, 'create'));        
        return $this->setObjProperties($obj);
    }
    
    /**
     * Set an object properties, the object must extends Document
     * 
     * @param Document $object      The object to set properties
     * @return Document             The object with setted properties
     */
    public function setObjProperties(Document $object)
    {        
        foreach($this->getValues() as $k => $v)
        {
            $method = 'set'.ucfirst(String::camelize($k));
            $object->$method($v);
        }
        
        return $object;
    }
    
    /**
     * Get the hidden captcha tags to insert into form
     * 
     * @param string $fieldId       [optional] hidden captcha hidden field name (default is hcptch)
     * @return strin                Return a tag to insert into form to secure
     */
    public static function getCaptchaTags($fieldId = "hcptch")
    {
        return f_form_Captcha::getFormTags($fieldId);
    }
    
    /**
     * Check a posted hidden captcha made with getCaptchaTags
     * 
     * @param string $formId        [optional] The id to use to generate input elements (default = "hcptch")
     * @param integer $minLimit     [optional] Submission minimum time limit in seconds (default = 5)
     * @param integer $maxLimit     [optional] Submission maximum time limit in seconds (default = 1200)
     * @return boolean              Return false if the submitter is a robot 
     */
    public static function checkCaptcha($fieldId = 'hcptch', $minLimit = 5, $maxLimit = 1200)
    {
        return f_form_Captcha::checkCaptcha($fieldId, $minLimit, $maxLimit);
    }
}