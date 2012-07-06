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
 * Invisible captcha for your forms
 */
class f_form_Captcha
{ 
    // Errors vars
    static $CAPTCHA_NO_SESSION_DATA = 10;
    static $CAPTCHA_VALUES_NOT_SUBMITTED = 20;
    static $CAPTCHA_TIME_LIMIT_EXCEEDED = 30;
    static $CAPTCHA_REMOTE_ADDRESS_ERROR = 40;
    static $CAPTCHA_SPAMBOT_AUTO_FILL = 50;    

    /**
     * Returns the hidden captcha tags to put in your form
     * 
     * @param string $formId    The id to use to generate input elements
     * @return string           The tags to put in your form
     */
    public static function getFormTags($formId = 'hcptch')
    {        
        // build the spinner key with current time and remote address, encrypt it
        $spinner = self::_generateSpinner();

        // put a random invisible style, to fool spambots a little bit ;-)
        $styles = array('position:absolute;left:-'.mt_rand(10000, 20000).'px;', 'display: none');        
        $style = $styles[array_rand($styles)];
        
        // build tags
        $tags  = '<input type="hidden" name="'.$formId.'[spinner]" value="'.$spinner.'" />'.PHP_EOL;
        $tags .= '<span style="'.$style.'"><input type="text" name="'.$formId.'[name]" value=""/></span>'.PHP_EOL;
 
        return $tags;
    }
        
    /**
     * Display the hidden captcha's tags to put in you form
     * 
     * @param string $formId    The id to use to generate input elements
     */
    public static function renderFormTags($formId = 'hcptch')
    {
        echo self::getFormTags($formId);
    }
    
    /**
     * Check the hidden captcha's values
     * 
     * @param string $formId        The id to use to generate input elements
     * @param integer $timeLimit    Submission time limit
     * @return boolean              Return false if the submitter is a robot 
     */
    public static function checkCaptcha($formId = 'hcptch', $timeLimit = 1200)
    {
        // get posted values
        $values = Request::getInstance()->post($formId);
        
        // check if all hidden fields are correctly filled
        if($values === null || !isset($values->spinner) || !isset($values->name) || $values->name != '') return false;
                
        // check if form is posted at the right time and from the right remote address
        if(!self::_checkSpinner($values->spinner, $timeLimit)) return false;
        
        // everything is ok, return true
        return true;
    }
    
    /**
     * Generate a spinner including session id, ip and user agent
     * 
     * @return string       The spinner key
     */
    private static function _generateSpinner()
    {
        return Security::encrypt(time() .'|'. Security::hash(session_id().self::_getIp().$_SERVER['HTTP_USER_AGENT']));
    }
    
    /**
     * Check the posted spinner
     * 
     * @param string $spinner       The spinner key
     * @param integer $timeLimit    Submission time limit
     * @return boolean 
     */
    private static function _checkSpinner($spinner, $timeLimit)
    {
        list($time, $hash) = explode('|', Security::decrypt($spinner));

        if(time() - $time < $timeLimit && Security::check(session_id().self::_getIp().$_SERVER['HTTP_USER_AGENT'], $hash)) return true;
        return false;
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
            else
            {
                return $_SERVER['HTTP_X_FORWARDED_FOR'];
            }
        }

        return $_SERVER['REMOTE_ADDR'];
    }    
}