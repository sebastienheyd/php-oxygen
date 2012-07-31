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
 * Checker for alphabetic characters only
 * 
 * Use the alpha keyword with Form->check() method
 */

class f_form_check_Alpha extends f_form_check_Abstract
{
    public function check()
    {
        return preg_match('/^([a-zA-Z]+)$/', $this->fieldValue);
    }    
    
    public function getError()
    {
        return $this->translate('%name% must contain only alphabetic characters');
    }
}