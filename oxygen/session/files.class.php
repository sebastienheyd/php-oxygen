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

class f_session_Files implements f_session_Interface
{
    protected $_savePath;
    protected $_sessionName;
    
    public function __construct($lifetime)
    {       
        $this->_savePath = CACHE_DIR.DS.'session';
        if (!is_dir($this->_savePath)) mkdir($this->_savePath, 0777);
        $this->_lifeTime = $lifetime;
    }

    public function open($savePath, $sessionName)
    {
        $this->_sessionName = $sessionName;
        return true;
    }

    public function close()
    {
        return true;
    }

    public function read($id)
    {
        return (string)@file_get_contents($this->_savePath.DS."sess_$id");
    }

    public function write($id, $data)
    {
        return file_put_contents($this->_savePath.DS."sess_$id", $data) === false ? false : true;
    }

    public function destroy($id)
    {
        $file = $this->_savePath.DS."sess_$id";
        if (file_exists($file)) unlink($file);
        return true;
    }

    public function gc($lifetime)
    {
        foreach (glob($this->_savePath.DS."sess_*") as $file) 
        {
            if (filemtime($file) + $lifetime < time() && file_exists($file)) unlink($file);
        }

        return true;
    }        
}