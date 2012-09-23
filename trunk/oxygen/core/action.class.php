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
abstract class Action
{
    public $view;
    public $cacheId;
    public $cacheLifetime = 3600;
    public $model = array();

    /**
     * Magic method to call non existent class methods
     * 
     * @param string $method    Method name
     * @param type $args        Method arguments
     * @return mixed            Return the method result if exist else throw an error
     */
    public function __call($method, $args)
    {
        switch ($method)
        {
            // return models
            case 'getModel':
                return $this->model;
                break;

            // alias of setModel()
            case 'addToModel':
                $this->setModel($args[0], $args[1]);
                break;

            // alias of t()
            case 'translate':
                $this->t($args[0], $args[1], $args[2], $args[3]);
                break;

            // return view current content
            case 'getView':
                return $this->view;
                break;

            default:
                throw new BadMethodCallException('Method ' . $method . ' does not exist');
                break;
        }
    }

    /**
     * Magic method to get non existant class property value
     * 
     * @param string $name      Name of the property to get
     * @return mixed            Return the property value
     */
    public function __get($name)
    {
        switch ($name)
        {
            case 'request':
                return Request::getInstance();
                break;
        }
    }

    public function hasCache($viewName, $cacheId)
    {
        preg_match('/^m_(.*)_action_(.*)/', get_class($this), $matches);

        $module   = $matches[1];
        $l        = explode('_', $matches[2]);
        $filename = lcfirst(end($l)) . ucfirst($viewName) . '.html';

        if ($file = get_module_file($module, 'template' . DS . $filename))
        {
            return Template::getInstance($file, $module)->hasCache($cacheId);
        }

        return false;
    }

    /**
     * Set the view name to display
     *
     * @param string $value             Name of the view to display
     * @param string $cacheId           [optional] Cache indentifier to use 
     * @param string $cacheLifetime     [optional] Cache lifetime 
     * @return void
     */
    public function setView($viewName, $cacheId = null, $cacheLifetime = 3600)
    {
        $this->view = $viewName;
        $this->cacheId = $cacheId;
        $this->cacheLifetime = $cacheLifetime;
    }

    /**
     * Set a model to pass to view
     *
     * @param string $name      Name of the model
     * @param mixed $value      Value of the model
     * @return Action           Current instance of Action
     */
    public function setModel($name, $value)
    {
        $this->model[$name] = $value;
        return $this;
    }

    /**
     * Translate the given string to the current i18n locale language.
     * 
     * @param type $string      The string to translate
     * @param type $args        [optional] Associative array of elements to replace in the given string.<br />Exemple : translate('My name is %name%', array('name' => 'Jim'))
     * @param type $domain      [optional] Name of the category of message (the xml file to get, ex : errors => errors.fr.xml). Default is 'messages'
     * @return string           The translated string if found, else the source string
     */
    public function t($string, $args = array(), $domain = 'actions')
    {
        // if current locale is equal to default locale we don't need to translate
        if (i18n::getLocale() === i18n::getDefaultLocale())
            return i18n::replaceArgs($string, $args);

        $class = get_class($this);
        if (preg_match('/^m_(.*)_action/i', $class, $m))
        {
            // Get locale file with full locale code (ex : fr_CA) for overloading
            if ($file = get_module_file($m[1], 'i18n' . DS . $domain . '.' . I18n::getLocale() . '.xml'))
            {
                $str = I18n::t($file, $string, $args, null, $class, false);
                if ($str !== $string)
                    return $str;   // There is a specific translation for full locale code, return
            }

            // If default lang is equal to the current lang : return
            if (i18n::getLang() === i18n::getDefaultLang())
                return i18n::replaceArgs($string, $args);

            // Get locale file with only lang code (ex : fr) or create it
            $file = get_module_file($m[1], 'i18n' . DS . $domain . '.' . I18n::getLang() . '.xml', false);
            return I18n::t($file, $string, $args, null, $class);
        }

        return $string;
    }

}