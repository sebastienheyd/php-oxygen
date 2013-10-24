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
abstract class AjaxAction extends Action
{
    public function isAuthorized()
    {
        // Check if we have an XMLHttpRequest context
        if(isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           $_SERVER['HTTP_X_REQUESTED_WITH'] !== '' && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') 
            return true;
        
        // Check if we call from the same referer
        if(isset($_SERVER['HTTP_REFERER']) && 
           $_SERVER['HTTP_REFERER'] !== '' && 
           preg_match('#^https?://'.$_SERVER['HTTP_HOST']."#", $_SERVER['HTTP_REFERER']))
            return true;
        
        return false;
    }
    
    public function errorHandler() 
    {
        Error::show401();
    }
}