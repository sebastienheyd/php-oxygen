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
        if(!is_file($keyFile))
        {
            file_put_contents($keyFile, String::random(32, true, true, true));
        }
        
        if(!is_file($keyFile)) trigger_error('Cannot write the security key in '.WEBAPP_DIR.DS.'config', E_USER_ERROR);
        
        return file_get_contents($keyFile);
    }
    
    /**
     * AES Decrypt a string, very useful for reading encrypted passwords from database per example
     * 
     * @param string $val   The string to decrypt
     * @param string $ky    (optionnal) The key to use for decryption, by default use the application key
     * @return string       The decrypted value or crypted if key is incorrect
     */
    public static function aesDecrypt($val,$ky = null)
    {
        if(is_null($ky)) $ky = self::getKey();
        $val = base64_decode($val);
        $key="\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0";
        for($a=0;$a<strlen($ky);$a++)
          $key[$a%16]=chr(ord($key[$a%16]) ^ ord($ky[$a]));
        $mode = MCRYPT_MODE_ECB;
        $enc = MCRYPT_RIJNDAEL_128;
        $dec = @mcrypt_decrypt($enc, $key, $val, $mode, @mcrypt_create_iv( @mcrypt_get_iv_size($enc, $mode), MCRYPT_DEV_URANDOM ) );
        return rtrim($dec,(( ord(substr($dec,strlen($dec)-1,1))>=0 and ord(substr($dec, strlen($dec)-1,1))<=16)? chr(ord( substr($dec,strlen($dec)-1,1))):null));
    }

    /**
     * AES Encrypt a string. Very useful to save passwords in the database per example
     * 
     * @param string $val   The string to encrypt
     * @param string $ky    (optionnal) The key to use for encryption
     * @return string       The encrypted string in base64 (url compatible)
     */
    public static function aesEncrypt($val,$ky = null)
    {
        if(is_null($ky)) $ky = self::getKey();
        $key="\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0";
        for($a=0;$a<strlen($ky);$a++)
          $key[$a%16]=chr(ord($key[$a%16]) ^ ord($ky[$a]));
        $mode=MCRYPT_MODE_ECB;
        $enc=MCRYPT_RIJNDAEL_128;
        $val=str_pad($val, (16*(floor(strlen($val) / 16)+(strlen($val) % 16==0?2:1))), chr(16-(strlen($val) % 16)));
        return base64_encode(mcrypt_encrypt($enc, $key, $val, $mode, mcrypt_create_iv( mcrypt_get_iv_size($enc, $mode), MCRYPT_DEV_URANDOM)));
    }     
}