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
 * Check if X field value match to Y field value
 * 
 * Use the match keyword with Form->check() method
 * 
 * @example $form->check('password_check', 'match[password]'); -> password_check field must match password field
 */

class f_form_check_Match extends f_form_check_Abstract
{        
    public function check($arg)
    {
        return $this->post->$arg == $this->fieldValue;
    }    
    
    public function getError()
    {
        return $this->translate("%name% does not match");
    }
}