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
 * Checker for correctly formated values
 * 
 * Use the regex keyword with Form->check() method
 * 
 * @example : $form->check('field', 'regex[#^123#]'); -> field value must begin with 123
 */

class f_form_check_Regex extends f_form_check_Abstract
{
    public function check($regex)
    {
        if($regex == null) throw new InvalidArgumentException('Numeric check needs two arguments');      

        return preg_match($regex, $this->fieldValue);
    }    
    
    public function getError()
    {
        return $this->translate('%name% is not in the correct format');
    }
}