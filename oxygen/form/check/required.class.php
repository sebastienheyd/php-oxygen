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
 * Check if field is filled
 * 
 * Use the required keyword with Form->check() method
 */

class f_form_check_Required extends f_form_check_Abstract
{
    public function check()
    {
        return !empty($this->fieldValue);
    }    
    
    public function getError()
    {
        return $this->translate('%name% field is required');
    }
}