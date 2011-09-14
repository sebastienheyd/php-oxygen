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

abstract class f_test_Abstract
{
    protected $testLabel = '';
    protected $label = '';
    private $_halt = false;

    /**
     *  Halt test on error
     */
    public function halt()
    {
        $this->_halt = true;
    }
    
    /**
     * Run the test
     * 
     * @return mixed    Return the test result as an array
     */
    public static function run()
    {
        $result = array();
                
        $class = get_called_class();        
        $methods = get_class_methods($class);
        $test = new $class();
        
        $result['class'] = str_replace('f_test_', '', $class);
        $result['label'] = $test->testLabel;
        $result['total'] = 0;

        foreach($methods as $method)
        {
            if(strncasecmp('test_', $method, 5) == 0) $result['total'] += 1;
        }
        
        foreach($methods as $method)
        {
            if(strncasecmp('test_', $method, 5) == 0)
            {
                $test->label = '';
                
                $testResult = $test->$method();
                
                $label = $test->label == '' ? str_replace('test_', '', $method) : $test->label;

                if($test->_halt) $label .= ' (halted)';
                
                $method = str_replace('test_', '', $method);
                $result['results'][] = array($label, $testResult);
                
                if($test->_halt)
                {
                    return $result;
                }        
            }
        }

        return $result;
    }    
}