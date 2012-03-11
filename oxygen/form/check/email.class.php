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
 * Checker for e-mail format
 * 
 * Use the email keyword with Form->check() method
 */

class f_form_check_Email extends f_form_check_Abstract
{
    public function check()
    {
        return String::checkEmail($this->fieldValue);
    }    
    
    public function getError()
    {
        return $this->translate('The e-mail address is not valid');
    }
}