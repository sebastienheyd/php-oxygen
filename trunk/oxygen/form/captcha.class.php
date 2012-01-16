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
        $spinner = Security::encode(serialize(array(time(), $_SERVER['REMOTE_ADDR'])));
 
        // put a random invisible style, to fool spambots a little bit ;-)
        $styles = array('position:absolute;left:-'.mt_rand(10000, 20000).'px;', 'display: none');        
        $style = $styles[array_rand($styles)];
        
        // build tags
        $tags  = '<input type="hidden" name="'.$formId.'[spinner]" value="'.$spinner.'" />'."\r\n";
        $tags .= '<span style="'.$style.'"><input type="text" name="'.$formId.'[name]" value=""/></span>'."\r\n";
 
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
        if(is_null($values) || !isset($values->spinner) || !isset($values->name) || $values->name != '')
        {
            return false;
        }        
        
        // get datas from the encrypted spinner key
        list($timestamp, $remoteAddr) = @unserialize(Security::decode($values->spinner));
        
        // check if form is posted at the right time and from the right remote address
        if(time() - $timestamp > $timeLimit || $_SERVER['REMOTE_ADDR'] != $remoteAddr)
        {
            return false;
        }        
        
        // everything is ok, return true
        return true;
    }
}