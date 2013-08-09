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

class f_auth_Db extends f_auth_Driver
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
            if(Security::check($login.$password, $hash)) return $this->login($login, $remember);
        }
        
        return false;
    }
    
    protected function retrieve($login)
    {
        if($this->retrieveUser($login))
        {
            $data = Db::select()->from(Config::get('auth.db_table', 'users'))
                                ->where(Config::get('auth.db_login_field', 'login'), $login)
                                ->fetch();
            
            unset($data[Config::get('auth.db_hash_field', 'password')]);
            
            return $data;
        }
        return null;
    }
    
    private function retrieveUser($login)
    {
        return Db::select(Config::get('auth.db_hash_field', 'password'))
                ->from(Config::get('auth.db_table', 'users'))
                ->where(Config::get('auth.db_login_field', 'login'), $login)
                ->fetchCol(Config::get('auth.db_config', 'db1'));
    }
}