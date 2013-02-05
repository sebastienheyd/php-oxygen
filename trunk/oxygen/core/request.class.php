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
    protected $_get = array();
    protected $_post = array();

    /**
     * Get f_Request instance. Singleton
     *
     * @return Request
     */
    public static function getInstance()
    {
        if (!isset(self::$_instance)) self::$_instance = new self();
        return self::$_instance;
    }

    private function __construct()
    {        
        if (isset($_GET) && !empty($_GET))   $this->_get = $_GET;
        if (isset($_POST) && !empty($_POST)) $this->_post = $_POST;
    }

    /**
     * Get variable value from $_GET
     *
     * @param string|null $name [optional] Name of the variable to get from $_GET. If null return whole container
     * @param mixed $default    [optional] Default value if variable does not exist in $_GET. Default is false
     * @return mixed|null
     */
    public function get($name = null, $default = false)
    {
        if($name === null) return $this->_get;
        return isset($this->_get[$name]) ? $this->_get[$name] : $default;
    }

    /**
     * Get variable value from $_POST
     *
     * @param string|null $name      [optionnal] Name of the variable to get from $_POST. If null return whole container
     * @param mixed $default    [optional] Default value if variable does not exist in $_POST. Default is false
     * @return mixed|null
     */
    public function post($name, $default = false)
    {
        if($name === null) return $this->_post;
        return isset($this->_post[$name]) ? $this->_post[$name] : $default;
    }

}