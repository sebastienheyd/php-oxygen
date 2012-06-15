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
    public static function expire($value, $unit = 's')
    {
        $units = array('s' => 1, 'm' => 60, 'h' => 3600, 'd' => 86400);
        if(!in_array($unit, array_keys($units))) throw new InvalidArgumentException('Unknown unit : '.$unit);
        return $value * $units[$unit];        
    }
    
    /**
     *
     * @param type $name
     * @return boolean 
     */
    public static function get($name)
    {
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
                    
        $value = Security::decode($value);
        if($v = unserialize($value)) $value = $v;
        return $value;              
    }
    
    /**
     *
     * @param type $name
     * @param type $value
     * @param type $lifetime
     * @param type $path
     * @param type $domain
     * @param type $secure
     * @param type $httponly
     * @return type
     * @throws OverflowException 
     */
    public static function set($name, $value = '', $lifetime = 3600, $path = null, $domain = null, $secure = null, $httponly = null)
    {
        // false value will delete the cookie so we force the value as an integer
        if($value === true || $value === false) $value = (int) $value;
        if($value === null) return setrawcookie($name, false, time() - 3600);
        
        if(is_array($value) && !empty($value)) $value = serialize($value);
        if(is_string($value) && $value !== '')
        {
            $value = Security::encode($value);                
            if ( strlen( $value ) > ( 4 * 1024 ) ) throw new OverflowException("The cookie $name exceeds the maximum cookie size. Some data may be lost");            
        }
        
        $_COOKIE[$name] = $value;        
        return setrawcookie($name, $value, time() + $lifetime, $path, $domain, $secure, $httponly);
    }
    
    /**
     *
     * @param type $name
     * @return type 
     */
    public static function delete($name)
    {
        unset($_COOKIE[$name]);
        return self::set($name, null);
    }
}
