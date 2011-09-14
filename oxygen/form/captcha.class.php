<?php
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
     * Display or return the hidden captcha tags to put in your form
     * 
     * @param boolean $echo
     * @return null|string
     */
    public static function getFormTags($formId = 'hcptch')
    {        
        $spinner = Security::aesEncrypt(serialize(array(time(), $_SERVER['REMOTE_ADDR'])));
 
        $styles = array('position:absolute;left:-15000px;', 'display: none');
        
        $style = $styles[array_rand($styles)];
        
        $tags  = '<input type="hidden" name="'.$formId.'[spinner]" value="'.$spinner.'" />'."\r\n";
        $tags .= '<span style="'.$style.'"><input type="text" name="'.$formId.'[name]" value=""/></span>'."\r\n";
 
        return $tags;
    }
    
    public static function renderFormTags($formId = 'hcptch')
    {
        echo self::getFormTags($formId);
    }
    
    public static function checkCaptcha($formId = 'hcptch', $timeLimit = 1200)
    {
        $values = Request::getInstance()->post($formId);
        
        if(is_null($values) || !isset($values->spinner) || !isset($values->name) || $values->name != '')
        {
            return false;
        }        
        
        list($timestamp, $remoteAddr) = unserialize(Security::aesDecrypt($values->spinner));
        
        if(time() - $timestamp > $timeLimit || $_SERVER['REMOTE_ADDR'] != $remoteAddr)
        {
            return false;
        }        
        
        return true;
    }
}