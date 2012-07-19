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

class f_auth_File extends f_auth_Driver
{
    public static function getInstance()
    {
        if(!isset(self::$_instance)) self::$_instance = new self();
        return self::$_instance;
    }    
    
    public function attempt($login, $password, $remember = false)
    {
        if($hash = $this->retrieveUser($login))
        {
            if(Security::check($password, $hash)) return $this->login($login, $remember);
        }
        
        return false;
    }
    
    protected function retrieve($login)
    {
        if($this->retrieveUser($login)) return $login;
        return null;
    }
    
    private function retrieveUser($login)
    {
        $file = CONFIG_DIR.DS.'.users';
        
        if(!is_file($file) || !is_readable($file)) return false;

        $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        foreach($lines as $line)
        {
            list($l, $hash) = explode(':', $line);
            if($l === $login) return $hash;
        }
        
        return false;
    }
}