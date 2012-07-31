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

class Request
{
    private static $_instance;

    public $get;
    public $post;
    
    /**
     * Get f_Request instance. Singleton
     *
     * @return Request
     */
    public static function getInstance()
    {
        if(!isset(self::$_instance)) self::$_instance = new self();
        return self::$_instance;
    }
    
    private function __construct()
    {
        if(isset($_GET) && !empty($_GET))
        {
            $this->get = to_object($_GET);
            unset($_GET);
        }

        if(isset($_POST) && !empty($_POST))
        {
             $this->post = to_object($_POST);
             unset($_POST);
        }
    }

    /**
     * Check if a variable exists in $_GET
     *
     * @param string $name      Name of the variable in $_GET
     * @return boolean          Return true if variable exist in $_GET
     */
    public function hasGet($name)
    {
        return isset($this->get->$name);
    }

    /**
     * Check if a variable exists in $_POST
     *
     * @param string $name      Name of the variable in $_POST
     * @return boolean          Return true if variable exist in $_POST
     */
    public function hasPost($name)
    {
        return isset($this->post->$name);
    }

    /**
     * Get variable value from $_GET
     *
     * @param string $name      Name of the variable to get from $_GET
     * @param mixed $default    [optional] Default value if variable does not exist in $_GET. Default is null
     * @return mixed|null
     */
    public function get($name, $default = null)
    {
        return $this->hasGet($name) ? $this->get->$name : $default;
    }

    /**
     * Get variable value from $_POST
     *
     * @param string $name      Name of the variable to get from $_POST
     * @param mixed $default    [optional] Default value if variable does not exist in $_POST. Default is null
     * @return mixed|null
     */
    public function post($name, $default = null)
    {
        return $this->hasPost($name) ? $this->post->$name : $default;
    }
}