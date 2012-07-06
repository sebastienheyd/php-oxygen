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
    public static function encrypt($text, $key = null)
    {      
        // Get the application key if no key is given
        if($key === null) $key = self::getKey();
        
        // To avoid same encoded string for the same string
        $text = Security::hash($text).'~~~'.$text;
        
        // Use openssl_encrypt with PHP >= 5.3.0
        if(function_exists('openssl_encrypt') && in_array('BF-OFB', openssl_get_cipher_methods()))
        {
            return openssl_encrypt($text, 'BF-OFB', $key);
        }
        // ... or use mcrypt if available
        else if (function_exists('mcrypt_encrypt'))
        {
            $size  = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB);
            $iv    = mcrypt_create_iv($size, MCRYPT_RAND);
            $crypt = mcrypt_encrypt(MCRYPT_RIJNDAEL_256, $key, $text, MCRYPT_MODE_ECB, $iv);
            return rtrim(strtr(base64_encode($crypt), '+/', '-_'), '=');
        }

        // ... else encrypt with xor technique
        $n = mb_strlen($text, '8bit');                
        $m = mb_strlen($key, '8bit');

        if($n !== $m) $key = mb_substr(str_repeat($key, ceil($n / $m)), 0, $n, '8bit');

        return base64_encode($text ^ $key); 
    }

    /**
     * Decrypts a string encoded with mcrypt.<br />
     * If mcrypt is not available, decrypts with xor
     * 
     * @param string $text      The text to decode
     * @param string $key       [optionnal] The key to use. Default is the application key
     * @return string           The decrypted string
     */    
    public static function decrypt($text, $key = null)
    {        
        // Get the application key if no key is given
        if($key === null) $key = self::getKey();
        
        // Use openssl_decrypt with PHP >= 5.3.0
        if(function_exists('openssl_decrypt') && in_array('BF-OFB', openssl_get_cipher_methods()))
        {
            $msg = openssl_decrypt($text, 'BF-OFB', $key);
        }
        // ... or use mcrypt if available
        else if (function_exists('mcrypt_encrypt'))
        {
            $size  = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB);
            $iv    = mcrypt_create_iv($size, MCRYPT_RAND);
            $text  = base64_decode(str_pad(strtr($text, '-_', '+/'), strlen($text) % 4, '=', STR_PAD_RIGHT));
            $msg = rtrim(mcrypt_decrypt(MCRYPT_RIJNDAEL_256, $key, $text, MCRYPT_MODE_ECB, $iv), "\0");
        }
        else
        {            
            // ... else decrypt with xor technique
            $n = mb_strlen($text, '8bit');                
            $m = mb_strlen($key, '8bit');

            if($n !== $m) $key = mb_substr(str_repeat($key, ceil($n / $m)), 0, $n, '8bit');

            $msg = base64_decode($text) ^ $key;
        }

        // To avoid truncated encoded strings
        list($hash, $value) = explode('~~~', $msg);       
        if(Security::check($value, $hash)) return $value;
        
        return false;
    }   
    
   /**
    * Generate a hash value
    * 
    * @param string $data       Message to be hashed
    * @param integer $rounds    Number of rounds for the Blowfish algorithm
    * @return string            The hash value
    */
    public static function hash($data, $rounds = 8)
    {
        if(function_exists('openssl_random_pseudo_bytes'))
        {
            $salt = openssl_random_pseudo_bytes(16);
        }
        else
        {
            $salt = String::random(array('length' => 40));
        }

        $salt = substr(strtr(base64_encode($salt), '+', '.'), 0 , 22);

        // Better use Blowfish with PHP >= 5.3.0 or if configuration is defined for retrocompatibility
        if(CRYPT_BLOWFISH === 1 && Config::get('security','hash_force_md5') === false) return crypt(base64_encode($data), sprintf('$2a$%02d$', $rounds).$salt);            

        // ... else use md5
        if(CRYPT_MD5 === 1) return crypt(base64_encode($data), '$1$'.$salt);
    }
    
    /**
     * Check if given hash is correct
     * 
     * @param string $value     Value to check
     * @param string $hash      Hash value
     * @return boolean          Return true if hash corresponding to the given value
     */
    public static function check($value, $hash)
    {
        return crypt(base64_encode($value), $hash) === $hash;
    }    
}