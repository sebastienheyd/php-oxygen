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

class Log
{    
    const DEBUG = 40;
    const INFO  = 30;
    const WARN  = 20;
    const ERROR = 10;
    const OFF   = 0;
    
    private static $_level;
    private static $_inst;
    private static $_instance;
    
    /**
     * @return Log 
     */
    public static function init()
    {
        if(!isset(self::$_instance)) self::$_instance = new self();
        return self::$_instance;
    }      
    
    /**
     * Constructor 
     */
    private function __construct()
    {
        $level = strtoupper(Config::get('debug.logging_level', 'off'));
        
        $authorized = array('DEBUG', 'INFO', 'WARN', 'ERROR');
        if(!in_array($level, $authorized)) $level = 'OFF';    
        
        self::$_level = constant("self::".$level);
        if(self::$_level !== self::OFF) self::$_inst = $this->_getClass();
    }
    
    /**
     * Get log engine class instance to use
     * 
     * @return f_log_Driver 
     */
    private function _getClass()
    {        
        // Check for authorized engines
        $authorized = array('file', 'firephp', 'stream');

        // Get config
        $config = strtolower(Config::get('debug.logging_handler', 'file'));     
        if(!in_array($config, $authorized)) die($config.' is not a valid logging handler');
                        
        // Instanciation
        return call_user_func(array('f_log_'.ucfirst($config), 'getInstance'));
    }         
                               
    /**
     * Get the backtrace to get the action before calling Log classe
     * 
     * @param array $exclude    Array of pattern to exclude from file name
     * @return string           Last file and line called before logging
     */
    protected static function _getBacktrace($exclude = array())
    {
        foreach(debug_backtrace() as $trace)
        {
            if(isset($trace['class']) && strstr($trace['class'], 'Log')) continue;
            if(preg_match('#'.join('|', $exclude).'#', $trace['file'])) continue;
            return str_replace(APP_DIR, '', $trace['file']).' (ln.'. $trace['line'].')';                
        }
    }
    
    /**
     * Log a debug information, only logged when level == DEBUG
     * 
     * @param string $msg   The message to log
     */    
    public static function debug($msg)
    {
        if(self::$_level === self::DEBUG) self::$_inst->write('debug', $msg);
    }     
    
    /**
     * Log an information, only logged when level >= INFO
     * 
     * @param string $msg   The message to log
     */
    public static function info($msg)
    {
        if(self::$_level >= self::INFO) self::$_inst->write('info', $msg);
    }     
    
    /**
     * Log a warn information, only logged when level >= WARN
     * 
     * @param string $msg   The message to log
     */    
    public static function warn($msg)
    {
        if(self::$_level >= self::WARN) self::$_inst->write('warning', $msg);
    }     
    
    /**
     * Log an error event, only logged when level >= ERROR
     * 
     * @param string $msg   The message to log
     */     
    public static function error($msg)
    {
        if(self::$_level >= self::ERROR) self::$_inst->write('error', $msg);
    }    
    
    /**
     * Log an sql query, only logged when level >= INFO
     * 
     * @param string $msg   The message to log
     */       
    public static function sql($msg)
    {
        if(self::$_level >= self::INFO)
        {            
            if(self::$_level === self::DEBUG) self::$_inst->write('debug', '{Db->execute()} Call from '.self::_getBacktrace(array('/db/')));
            self::$_inst->write('sql', $msg);
        }
    }
}