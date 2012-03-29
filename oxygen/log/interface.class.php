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

interface f_log_Interface
{
    /**
     * Write a message to a log file
     * 
     * @param string $fileName      Log name
     * @param string $msg           The message to log        
     * @return boolean
     */
    public function write($type, $msg);
}