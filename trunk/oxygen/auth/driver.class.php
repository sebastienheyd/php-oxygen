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

abstract class f_auth_Driver
{
    const SESSION_NAME = 'auth_login';
    const COOKIE_NAME = 'auth_remember';
    
    protected static $_instance;
    
    protected $user;
    
    protected $token;   
    
    public function __construct()
    {
        $this->token = Session::get(self::SESSION_NAME, $this->recall());
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
    abstract public function attempt($login, $password); 
    
    /**
     * Retrieve the user linked to the token
     * 
     * @return mixed|null 
     */
    abstract protected function retrieve($token);
    
    public function isLogged()
    {
        return $this->getUser() !== null;
    }
    
    public function getUser()
	{
		if ($this->user !== null) return $this->user;
		return $this->user = $this->retrieve($this->token);
	}    
    
    public function login($token, $remember = false)
    {
        $this->token = $token;

        Session::set(self::SESSION_NAME, $token);

		if ($remember) Cookie::set(self::COOKIE_NAME, $token, Cookie::expire('365', 'd'));

		return true;
    }
    
    public function logout()
    {
        $this->user = null;
        $this->token = null;
        Cookie::delete(self::COOKIE_NAME);
        Session::delete(self::SESSION_NAME);
    } 
    
    protected function recall()
	{
        if($cookie = Cookie::get(self::COOKIE_NAME)) return $cookie;       
        return null;
	}
}