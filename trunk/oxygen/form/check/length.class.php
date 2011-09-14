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
 * Checker for string length
 * 
 * Use the length keyword with Form->check() method
 * 
 * @example : $form->check('login', 'length[3]'); -> length must have at least 3 chars
 * @example : $form->check('login', 'length[3, 10]'); -> length must have at least 3 chars and have a maximum of 10 chars
 */

class f_form_check_Length extends f_form_check_Abstract
{
    private $_errorMsg;
    
    public function check($args)
    {
        $args = explode(',', $args);
        
        if(empty($args)) trigger_error('Length check args must contain a min and a max value', E_USER_ERROR);
        
        $length = strlen($this->fieldValue);       
        
        if(count($args) == 1)
        {
            if($length < $args[0])
            {
                $this->_errorMsg = $this->translate('%name% must have at least %nb% characters', array('nb' => $args[0]));
                return false;
            }
        }
        else
        {
            if($args[0] == 0 && $args[1] > 0 && $length > $args[1])
            {
                $this->_errorMsg = $this->translate('%name% must not exceed %nb% characters', array('nb' => $args[1]));
                return false;
            }

            if($args[0] > 0 && $args[1] > 0 && $args[0] == $args[1] && $length != $args[0])
            {
                $this->_errorMsg = $this->translate('%name% must have at least %nb% characters', array('nb' => $args[0]));
                return false;
            }

            if($length < $args[0] || $length > $args[1])
            {
                $this->_errorMsg = $this->translate('%name% must be between %nb1% and %nb2% characters', array('nb1' => $args[0], 'nb2' => $args[1]));
                return false;
            }            
        }

        
        return true;
    }    
    
    public function getError()
    {
        return $this->_errorMsg;
    }
}