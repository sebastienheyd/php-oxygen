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

class f_log_Firephp implements f_log_Interface
{
    private static $_instance;
    
    private $_fbInst;
    
    /**
     * @return f_log_Firephp 
     */
    public static function getInstance()
    {
        if(!isset(self::$_instance)) self::$_instance = new self();
        return self::$_instance;  
    }
    
    private function __construct()
    {
        require_once(FW_DIR.DS.'lib'.DS.'vendor'.DS.'firephp'.DS.'FirePHP.class.php');        
        if(!class_exists('FirePHP')) die('Cannot instanciate FirePHP');
        $this->_fbInst = FirePHP::getInstance(true);
    }
    
    public function write($type, $msg)
    {
        try
        {
            switch ($type)
            {
                case 'error':
                    $this->_fbInst->error($msg);
                break;
            
                case 'warning':
                    $this->_fbInst->warn($msg);
                break;
            
                case 'info':
                case 'sql':
                    $this->_fbInst->info($msg);
                break;

                default:
                        $this->_fbInst->log($msg);
                break;
            }
        }
        catch(Exception $e)
        {
            return;                  
        }
    }
}