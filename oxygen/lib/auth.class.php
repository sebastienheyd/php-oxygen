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

class Auth
{
    private static $_handler;    
    
    /**
     * Get the auth driver instance
     * 
     * @return f_auth_Driver 
     */
    private static function getDriver()
    {
        if(self::$_handler !== null) return self::$_handler;
        
        $handler = 'f_auth_'.ucfirst( Config::get('auth.handler', 'file'));
        
        return self::$_handler = new $handler();
    }
    
    /**
     * Attempt to log a user
     * 
     * @param string $login         Login to check
     * @param string $password      Password associated to the login
     * @param boolean $remember     [optional] Remember the login with a cookie ? Default is false
     * 
     * @return boolean              Return true if connexion attempt is successful 
     */
    public static function attempt($login, $password, $remember = false)
    {
        return self::getDriver()->attempt($login, $password, $remember);
    }
    
    /**
     * Check if the user is logged in or not
     * 
     * @return boolean 
     */
    public static function isLogged()
    {
        return self::getDriver()->isLogged();
    }
    
    /**
     * Get the current logged user
     * 
     * @return mixed|null 
     */
    public static function getUser()
    {
        return self::getDriver()->getUser();
    }
    
    /**
     * Log in the user assigned to the given token.<br />
     * Use to log a user without checking the password.
     * 
     * @param string $token         The token to log in, typically a numeric ID
     * @param boolean $remember     [optional] Remember the login with a cookie ? Default is false
     * @param string $cookiePath    [optional] Set the cookie path. Default is '/'
     * @return boolean 
     */
    public static function login($token, $remember = false, $cookiePath = '/')
    {
        return self::getDriver()->login($token, $remember, $cookiePath);
    }
    
    /**
     * Log the user out
     * 
     * @return void 
     */
    public static function logout()
    {
        return self::getDriver()->logout();
    }
}