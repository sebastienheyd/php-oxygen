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
    static $CAPTCHA_IMAGE_ERROR = 10;
    static $CAPTCHA_TIME_LIMIT_ERROR = 20;
    static $CAPTCHA_SPAMBOT_AUTO_FILL = 30;   
    static $CAPTCHA_HFIELD_ERROR = 40;
    static $CAPTCHA_SPINNER_ERROR = 50;
    static $CAPTCHA_VALUES_NOT_SUBMITTED = 60;

    /**
     * Returns the hidden captcha tags to put in your form
     * 
     * @param string $formId        [optional] The id to use to generate input elements (default = "hcptch")
     * @param boolean $withImage    [optional] The captcha use the classic image captcha
     * @return string               The tags to put in your form
     */
    public static function getFormTags($formId = 'hcptch', $withImage = false)
    {        
        // Get spinner vars
        $now = time();
        $name = String::random(array('numbers' => false, 'uppercase' => false));
        
        // Generate the spinner
        $spinner = array(
            'timestamp'     => $now,
            'session_id'    => session_id(),
            'ip'            => self::_getIp(),
            'user_agent'    => $_SERVER['HTTP_USER_AGENT'],
            'hfield_name'   => $name
        );
        
        if($withImage)
        {
            $captcha = String::hrRandom(5);
            $spinner['captcha'] = $captcha;
        }  
        
        // Encrypt the spinner
        $spinner = Security::encrypt(serialize($spinner));

        // put a random invisible style, to fool spambots a little bit ;-)
        $styles = array('position:absolute;left:-'.mt_rand(10000, 20000).'px;', 'display: none');        
        $style = $styles[array_rand($styles)];
        
        // build tags
        $tags  = '<input type="hidden" name="'.$formId.'[spinner]" value="'.$spinner.'" />'.PHP_EOL;
        $tags .= '<span style="'.$style.'"><input type="text" name="'.$formId.'[name]" value=""/></span>'.PHP_EOL;
        
        if($withImage)
        {
            $tags .= self::_generateImage($captcha);
            $tags .= '<input type="text" name="'.$formId.'['.$name.']" value="" autocomplete="off" />'.PHP_EOL;
        }
        else
        {
            $tags .= '<input type="hidden" name="'.$formId.'['.$name.']" value="'.$now.'" />'.PHP_EOL;
        }        
 
        return $tags;
    }
        
    /**
     * Display the hidden captcha's tags to put in you form
     * 
     * @param string $formId    [optional] The id to use to generate input elements
     */
    public static function renderFormTags($formId = 'hcptch', $withImage = false)
    {
        echo self::getFormTags($formId, $withImage);
    }
    
    /**
     * Generate a random "captcha" picture with the given text on it
     * 
     * @param type $value
     * @return type
     */
    private static function _generateImage($value)
    {
        // Création de l'image
        $img = imagecreatetruecolor(60, 25);
        
        // Allocation des couleurs
        $c1 = imagecolorallocate($img, 255, 120, 10);
        $c2 = imagecolorallocate($img, 51, 204, 204);
        $c3 = imagecolorallocate($img, 51, 153, 204);
        $c4 = imagecolorallocate($img, rand(204,255), rand(204,255), rand(204,255));
        
        // Création des lignes aléatoires
        imageline($img, rand(5, 20), 1, rand(30, 60), 60, $c1);
        imageline($img, 1, 60, rand(30, 60), 0, $c2);
        imageline($img, 1, rand(10, 20), 60, rand(10, 20), $c3);
        
        // Génération des pixels aléatoires
        for ($i = 0; $i < 1000; $i++) 
        {
            $c = imagecolorallocate($img, rand(50, 170), rand(50, 170), rand(50, 170));
            imagesetpixel($img, rand(1, 60),rand(1, 25), $c);
        }
        
        // Création du texte aléatoire
        imagestring($img, 5, rand(1, 12), rand(1, 8), $value, $c4);
        
        // Récupération de l'image en base64
        ob_start();
            $image = imagepng($img, null, 9);
            $i = ob_get_contents();
        ob_end_clean();        
        
        // Affichage
        return '<img src="data:image/png;base64,'.base64_encode($i).'" alt="" />';
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
        if($values === null || !isset($values['spinner']) || !isset($values['name']))
        {
            self::$_error = self::$CAPTCHA_VALUES_NOT_SUBMITTED;
            return false;
        }

        // Hidden field is set
        if($values['name'] !== '')
        {
            self::$_error = self::$CAPTCHA_SPAMBOT_AUTO_FILL;
            return false;
        }
        
        // Get the spinner values
        $spinner = Security::decrypt($values['spinner']);
        $spinner = @unserialize($spinner); 
        
        // Spinner is null or unserializable
        if(!$spinner || !is_array($spinner) || empty($spinner))
        {
            self::$_error = self::$CAPTCHA_SPINNER_ERROR;  
            return false;
        }
        
        // Check the random posted field
        $hField = $values[$spinner['hfield_name']];
        if(!isset($spinner['captcha']) && (!isset($hField) || $hField === ''))
        {
            self::$_error = self::$CAPTCHA_VALUES_NOT_SUBMITTED;
            return false;
        }        
        
        // Check time limits
        $now = time();        
        
        if($now - $spinner['timestamp'] < $minLimit || $now - $spinner['timestamp'] > $maxLimit)
        {
            self::$_error = self::$CAPTCHA_TIME_LIMIT_ERROR;
            return false;
        }
        
        // We have a classic captcha with an image
        if(isset($spinner['captcha']))
        {
            if(strtolower($hField) !== $spinner['captcha'])
            {
                self::$_error = self::$CAPTCHA_IMAGE_ERROR;
                return false;
            }
        }
        else
        {
            // Check if the random field value is similar to the spinner value
            if(!ctype_digit($hField) || $spinner['timestamp'] != $hField)
            {
                self::$_error = self::$CAPTCHA_HFIELD_ERROR;
                return false;
            }               
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