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

/**
 * Abstract class for form checkers
 */
abstract class f_form_check_Abstract
{
    protected $post;
    protected $fieldName;
    protected $fullName;
    
    /**
     * Main constructor
     * 
     * @param array $post           Array of posted values
     * @param string $fieldName     Field name to check
     * @param string $fullName      Field name in full text (for error display)
     */
    public function __construct($post, $fieldName, $fullName)
    {
        $this->post = $post;
        $this->fieldName = $fieldName;
        $this->fullName = $fullName;
        $this->fieldValue = $post->$fieldName;
    }    
    
    /**
     * You must have a getError method in your checkers classes
     */
    abstract public function getError();
    
    /**
     * Translate error message
     * 
     * @param string $string    String to translate
     * @param array $opt        Array of arguments to pass to the translation (like field name)
     * @return string           The translated string if exists else the original string
     */
    protected function translate($string, $opt = array())
    {
        $args = !empty($this->fullName) ? array('name' => $this->fullName) : array('name' => $this->fieldName);
        $args = array_merge($args, $opt);        
        
        $moduleName = get_module_name(get_class($this));
        
        if(!is_null($moduleName))
        {
            $file = MODULES_DIR.DS.$moduleName.DS.'i18n'.DS.'rules.'.I18n::getLang().'.xml';
        }
        else
        {
            $file = FW_DIR.DS.'lib'.DS.'form'.DS.'i18n'.DS.'rules.'.I18n::getLang().'.xml';
        }
        
        return I18n::translate($file, $string, $args, 'en', 'rules', true);
    }
}