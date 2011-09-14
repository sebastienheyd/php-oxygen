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

require_once(FW_DIR.DS.'lib'.DS.'vendor'.DS.'wideimage'.DS.'WideImage.php');

class Image extends WideImage 
{
    public $source;
    
    public static function load($source)
    {
        $instance = parent::load($source);        
        $instance->source = $source;
        $instance->basename = basename($source);
        $instance->name = substr($instance->basename, 0, strrpos($instance->basename, '.'));
        $instance->extension = substr($instance->basename, strrpos($instance->basename, '.'));
        return $instance;
    }
}