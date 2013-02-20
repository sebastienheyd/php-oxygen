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

/**
 * Checker for integer values
 * 
 * Use the integer keyword with Form->check() method
 * 
 * @example : $form->check('field', 'integer'); -> field value must be an integer
 */

class f_form_check_Integer extends f_form_check_Abstract
{
    public function check($args)
    {
        if($args !== null) $args = explode(',', $args);
        
        if(!( is_int($this->fieldValue) || ctype_digit($this->fieldValue) ))
        {
            $this->_errorMsg = $this->translate('%name% must contain an integer', array('nb' => $args[0]));
            return false;        
        }
        
        if($args === null) return true;

        if(count($args) != 2) throw new InvalidArgumentException('Numeric check needs two arguments');        
        
        if($this->fieldValue < $args[0])
        {
            $this->_errorMsg = $this->translate('%name% must contain a number greater than or equal to %nb%', array('nb' => $args[0]));
            return false; 
        }
        
        if($this->fieldValue > $args[1])
        {
            $this->_errorMsg = $this->translate('%name% must contain a number less than or equal to %nb%', array('nb' => $args[1]));
            return false; 
        }
        
        return true;
    }    
    
    public function getError()
    {
        return $this->_errorMsg;
    }
}