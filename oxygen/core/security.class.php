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

class Security
{       
    /**
     * Return the unique key for the application or generate a new one and write it in webapp/config/.key
     * 
     * @return string
     */
    public static function getKey()
    {        
        $keyFile = WEBAPP_DIR.DS.'config'.DS.'.key';
        
        if(is_file($keyFile)) return file_get_contents($keyFile);
            
        $rand = String::random(array('length' => 32, 'special' => true));
        
        if(!file_put_contents($keyFile, $rand)) 
                trigger_error('Cannot write the security key in '.WEBAPP_DIR.DS.'config', E_USER_ERROR);       
        
        return $rand;
    }
    
    /**
     * Performs text encryption with mcrypt and returns it as a string.<br />
     * If mcrypt is not available, encrypts with xor
     * 
     * @param string $text      The text to encode
     * @param string $key       [optionnal] The key to use. Default is the application key
     * @return string           The encrypted string
     */
    public static function encode($text, $key = null)
    {
        if(!function_exists('mcrypt_encrypt')) return self::xorencode($text, $key);
        
        if($key === null) $key = self::getKey();
        
        $size  = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB);
        $iv    = mcrypt_create_iv($size, MCRYPT_RAND);
        $crypt = mcrypt_encrypt(MCRYPT_RIJNDAEL_256, $key, $text, MCRYPT_MODE_ECB, $iv);
        
        return rtrim(strtr(base64_encode($crypt), '+/', '-_'), '=');
    }
    
    /**
     * Decrypts a string encoded with mcrypt.<br />
     * If mcrypt is not available, decrypts with xor
     * 
     * @param string $text      The text to decode
     * @param string $key       [optionnal] The key to use. Default is the application key
     * @return string           The decrypted string
     */    
    public static function decode($text, $key = null)
    {
        if(!function_exists('mcrypt_encrypt')) return self::xordecode($text, $key);
        
        if($key === null) $key = self::getKey();
        
        $size  = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB);
        $iv    = mcrypt_create_iv($size, MCRYPT_RAND);
        $text  = base64_decode(str_pad(strtr($text, '-_', '+/'), strlen($text) % 4, '=', STR_PAD_RIGHT));
        
        return rtrim(mcrypt_decrypt(MCRYPT_RIJNDAEL_256, $key, $text, MCRYPT_MODE_ECB, $iv), "\0");
    }   
    
    /**
     * Performs text encryption with xor and returns it as a string.
     * 
     * @param string $text      The text to encode
     * @param string $key       [optionnal] The key to use. If null, will use application key
     * @return string           The encrypted string
     */    
    public static function xorencode($text, $key = null)
    {
        if($key === null) $key = self::getKey();
        
        $n = mb_strlen($text, '8bit');                
        $m = mb_strlen($key, '8bit');
        
        if($n != $m) $key = mb_substr(str_repeat($key, ceil($n / $m)), 0, $n, '8bit');
        
        return base64_encode($text ^ $key);    
    }
    
    /**
     * Decrypts a string encoded with xor.
     * 
     * @param string $text      The text to decode
     * @param string $key       [optionnal] The key to use. If null, will use application key
     * @return string           The decrypted string
     */     
    public static function xordecode($text, $key = null)
    {
        if($key === null) $key = self::getKey();
        
        $n = mb_strlen($text, '8bit');                
        $m = mb_strlen($key, '8bit');
        
        if($n != $m) $key = mb_substr(str_repeat($key, ceil($n / $m)), 0, $n, '8bit');
        
        return base64_decode($text) ^ $key; 
    }    
}