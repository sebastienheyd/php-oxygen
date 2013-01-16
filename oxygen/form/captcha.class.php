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
 * Invisible captcha for your forms
 */
class f_form_Captcha
{ 
    private static $_error = false;
    
    // Errors vars
    static $CAPTCHA_VALUES_NOT_SUBMITTED = 10;
    static $CAPTCHA_TIME_LIMIT_ERROR = 20;
    static $CAPTCHA_SPINNER_ERROR = 30;
    static $CAPTCHA_HFIELD_ERROR = 40;
    static $CAPTCHA_IMAGE_ERROR = 50;
    static $CAPTCHA_SPAMBOT_AUTO_FILL = 60;   

    /**
     * Returns the hidden captcha tags to put in your form
     * 
     * @param string $formId    [optional] The id to use to generate input elements (default = "hcptch")
     * @return string           The tags to put in your form
     */
    public static function getFormTags($formId = 'hcptch')
    {        
        // Get spinner vars
        $now = time();
        $name = String::hrRandom();
        
        // Generate the spinner
        $spinner = array(
            'timestamp'     => $now,
            'session_id'    => session_id(),
            'ip'            => self::_getIp(),
            'user_agent'    => $_SERVER['HTTP_USER_AGENT'],
            'hfield_name'   => $name
        );
        
        // Encrypt the spinner
        $spinner = Security::encrypt(serialize($spinner));

        // put a random invisible style, to fool spambots a little bit ;-)
        $styles = array('position:absolute;left:-'.mt_rand(10000, 20000).'px;', 'display: none');        
        $style = $styles[array_rand($styles)];
        
        // build tags
        $tags  = '<input type="hidden" name="'.$formId.'[spinner]" value="'.$spinner.'" />'.PHP_EOL;
        $tags .= '<span style="'.$style.'"><input type="text" name="'.$formId.'[name]" value=""/></span>'.PHP_EOL;
        $tags .= '<input type="hidden" name="hcptch['.$name.']" value="'.$now.'" />'.PHP_EOL;
 
        return $tags;
    }
        
    /**
     * Display the hidden captcha's tags to put in you form
     * 
     * @param string $formId    [optional] The id to use to generate input elements
     */
    public static function renderFormTags($formId = 'hcptch')
    {
        echo self::getFormTags($formId);
    }
    
    /**
     * Check the hidden captcha's values
     * 
     * @param string $formId        [optional] The id to use to generate input elements (default = "hcptch")
     * @param integer $minLimit     [optional] Submission minimum time limit in seconds (default = 5)
     * @param integer $maxLimit     [optional] Submission maximum time limit in seconds (default = 1200)
     * @return boolean              Return false if the submitter is a robot 
     */
    public static function checkCaptcha($formId = 'hcptch', $minLimit = 5, $maxLimit = 1200)
    {                
        // get posted values
        $values = Request::getInstance()->post($formId);
        
        // Check post values
        if($values === null || !isset($values->spinner) || !isset($values->name))
        {
            self::$_error = self::$CAPTCHA_VALUES_NOT_SUBMITTED;
            return false;
        }

        // Hidden field is set
        if($values->name !== '')
        {
            self::$_error = self::$CAPTCHA_SPAMBOT_AUTO_FILL;
            return false;
        }
        
        // Get the spinner values
        $spinner = Security::decrypt($values->spinner);
        $spinner = unserialize($spinner); 
        
        // Spinner is null or unserializable
        if(!$spinner || !is_array($spinner) || empty($spinner))
        {
            self::$_error = self::$CAPTCHA_SPINNER_ERROR;  
            return false;
        }
        
        // Check the random posted field
        $hField = $values->{$spinner['hfield_name']};
        if(!isset($hField) || $hField === '')
        {
            self::$_error = self::$CAPTCHA_VALUES_NOT_SUBMITTED;
            return false;
        }        
        
        // Check time limits
        $now = time();
        if($now - $hField < $minLimit || $now - $hField > $maxLimit)
        {
            self::$_error = self::$CAPTCHA_TIME_LIMIT_ERROR;
            return false;
        }
        
        // Check if the random field value is similar to the spinner value
        if(!ctype_digit($hField) || $spinner['timestamp'] != $hField)
        {
            self::$_error = self::$CAPTCHA_HFIELD_ERROR;
            return false;
        }   
        
        // Check spinner values
        if(!isset($spinner['session_id'], $spinner['ip'], $spinner['user_agent']) &&
               $spinner['session_id'] !== session_id &&
               $spinner['ip'] !== self::_getIp() && 
               $spinner['user_agent'] !== $_SERVER['HTTP_USER_AGENT']
        ){
            self::$_error = self::$CAPTCHA_SPINNER_ERROR;
            return false;
        }
           
        // Unset post values
        if(isset($_POST[$formId])) unset($_POST[$formId]);
        
        // everything is ok, return true
        return true;
    } 
    
    /**
     * Get the error code
     * 
     * @return integer
     */
    public static function getError()
    {
        return self::$_error;
    }

    /**
     * Get the client IP
     * 
     * @return string 
     */
    private static function _getIp()
    {
        if(!empty($_SERVER['HTTP_X_FORWARDED_FOR']))
        {
            if(strchr($_SERVER['HTTP_X_FORWARDED_FOR'],','))
            {
            	$tab = explode(',',$_SERVER['HTTP_X_FORWARDED_FOR']);
                return $tab[0];
            }
            
            return $_SERVER['HTTP_X_FORWARDED_FOR'];
        }

        return $_SERVER['REMOTE_ADDR'];
    }    
}