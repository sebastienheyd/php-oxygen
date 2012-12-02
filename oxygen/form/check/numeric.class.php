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
 * Checker for numeric values
 * 
 * Use the numeric keyword with Form->check() method
 * 
 * @example : $form->check('age', 'numeric'); -> field value must be numeric
 */

class f_form_check_Numeric extends f_form_check_Abstract
{
    public function check()
    {
        return is_numeric($this->fieldValue);
    }    
    
    public function getError()
    {
        return $this->translate('%name% must contain only numbers');
    }
}