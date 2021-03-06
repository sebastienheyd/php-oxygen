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

class f_cache_Null implements f_cache_Interface
{
    static $_instance;
        
    public function __construct(){}
    
    public function get($id)
    {
        return false;
    }
    
    public function save($id, $data, $ttl = 60)
    {
        return true;
    }
    
    public function delete($id)
    {
        return true;
    }
    
    public function flush()
    {
        return true;
    }
    
    public function isSupported()
    {
        return true;
    }
}