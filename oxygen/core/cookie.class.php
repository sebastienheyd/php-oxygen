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

class Cookie 
{
    /**
     * Return an expiration period length in seconds from a period in minutes or hours or days
     * 
     * @param integer $value    Period length to get
     * @param type $unit        [optionnal] Unit to use, "m" for minutes, "h" for hours, "d" for days
     * 
     * @return integer          Period length in seconds
     */
    public static function expire($value, $unit = 'm')
    {
        $units = array('m' => 60, 'h' => 3600, 'd' => 86400);
        if(!in_array($unit, array_keys($units))) throw new InvalidArgumentException('Unknown unit : '.$unit);
        return $value * $units[$unit];        
    }
    
    /**
     * Get cookie value(s)
     * 
     * @param string $name      Name of the cookie to get
     * @return mixed|false
     */
    public static function get($name)
    {
        // handling array notation
        if(preg_match('#^(.*?)\[(.*?)\]$#', $name, $m))
        {
            if(!isset($_COOKIE[$m[1]][$m[2]])) return false;
            $value = $_COOKIE[$m[1]][$m[2]];
        }
        else
        {
            if(!isset($_COOKIE[$name])) return false;
            $value = $_COOKIE[$name];
        }
                    
        // decode cookie content
        $value = Security::decode($value);
        
        // if content is a serialized array
        if($v = unserialize($value)) $value = $v;
        
        return $value;              
    }
    
    /**
     * Set value(s) into a cookie
     * 
     * @param string $name          The name of the cookie. 
     * @param mixed $value          Value to store into the cookie
     * @param integer $lifetime     [optional] The lifetime of the cookie in seconds. Default is 3600 (one hour)
     * @param string $path          [optional] The path on the server in which the cookie will be available on
     * @param string $domain        [optional] The domain that the cookie is available to
     * @param boolean $secure       [optional] Indicates that the cookie should only be transmitted over a secure HTTPS connection from the client
     * @param boolean $httponly     [optional] When TRUE the cookie will be made accessible only through the HTTP protocol.
     * 
     * @return boolean              Return true on success
     */
    public static function set($name, $value, $lifetime = 3600, $path = null, $domain = null, $secure = null, $httponly = null)
    {
        // false value will delete the cookie so we force the value as an integer
        if($value === true || $value === false) $value = (int) $value;
        
        // null value will delete the cookie (sets value as false)
        if($value === null) return setrawcookie($name, false, time() - 3600);
        
        // serialize arrays       
        if(is_array($value) && !empty($value)) $value = serialize($value);
        
        if(is_string($value) && $value !== '')
        {
            // encrypt the content
            $value = Security::encode($value);
            if ( strlen( $value ) > ( 4 * 1024 ) ) throw new OverflowException("The cookie $name exceeds the maximum cookie size. Some data may be lost");            
        }
        
        $_COOKIE[$name] = $value;        
        return setrawcookie($name, $value, time() + $lifetime, $path, $domain, $secure, $httponly);
    }
    
    /**
     * Delete a cookie
     * 
     * @param string $name        The name of the cookie. 
     * @return boolean            Return true on success
     */
    public static function delete($name)
    {
        unset($_COOKIE[$name]);
        return self::set($name, null);
    }
}
