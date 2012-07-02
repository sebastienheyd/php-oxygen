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

class Log
{
    static $_instance;
    
    const DEBUG = 40;
    const INFO  = 30;
    const WARN  = 20;
    const ERROR = 10;
    const OFF   = 0;
    
    private $_level;
    private $_inst;
    
    /**
     * @return Log 
     */
    public static function getInstance()
    {
        if(!isset(self::$_instance)) self::$_instance = new self();
        return self::$_instance;
    }      
    
    /**
     * Constructor 
     */
    private function __construct()
    {
        $level = strtoupper(Config::get('debug', 'logging_level', 'info'));
        
        $refl = new ReflectionClass('Log');
        $authorized = array_keys($refl->getConstants());
        if(!in_array($level, $authorized)) $level = 'OFF';    
        
        $this->_level = constant("self::".$level);
        $this->_inst = $this->_getClass();
    }
    
    /**
     * Get cache engine class instance to use
     * 
     * @return f_log_Driver 
     */
    private function _getClass()
    {        
        // Check for authorized engines
        $authorized = array('file', 'firephp', 'stream', 'null');

        // Get config
        $config = strtolower(Config::get('debug', 'logging_handler', 'null'));     
        if(!in_array($config, $authorized)) die($config.' is not a valid logging handler');
                        
        // Instanciation
        $inst =  call_user_func(array('f_log_'.ucfirst($config), 'getInstance'));        

        if($inst === null) die('Cannot instanciate '.$class);
        return $inst;
    }         
    
    /**
     * Return current log level
     * 
     * @return integer 
     */
    public function getLevel()
    {
        return $this->_level;
    }
    
    /**
     * Return instance of the current log handler
     * 
     * @return f_log_Interface 
     */
    public function getHandler()
    {
        return $this->_inst;
    }
                       
    /**
     * Get the backtrace to get the action before calling Log classe
     * 
     * @param array $exclude    Array of pattern to exclude from file name
     * @return string           Last file and line called before logging
     */
    public function getBacktrace($exclude = array())
    {
        foreach(debug_backtrace() as $trace)
        {
            if(isset($trace['class']) && strstr($trace['class'], 'Log')) continue;
            if(preg_match('#'.join('|', $exclude).'#', $trace['file'])) continue;
            return str_replace(PROJECT_DIR, '', $trace['file']).' (ln.'. $trace['line'].')';                
        }
    }
    
    /**
     * Log a debug information, only logged when level == DEBUG
     * 
     * @param string $msg   The message to log
     */    
    public static function debug($msg)
    {
        $inst = self::getInstance();
        if($inst->getLevel() === self::DEBUG) $inst->getHandler()->write('debug', $msg);
    }     
    
    /**
     * Log an information, only logged when level >= INFO
     * 
     * @param string $msg   The message to log
     */
    public static function info($msg)
    {
        $inst = self::getInstance();
        if($inst->getLevel() >= self::INFO) $inst->getHandler()->write('info', $msg);
    }     
    
    /**
     * Log a warn information, only logged when level >= WARN
     * 
     * @param string $msg   The message to log
     */    
    public static function warn($msg)
    {
        $inst = self::getInstance();
        if($inst->getLevel() >= self::WARN) $inst->getHandler()->write('warning', $msg);
    }     
    
    /**
     * Log an error event, only logged when level >= ERROR
     * 
     * @param string $msg   The message to log
     */     
    public static function error($msg)
    {
        $inst = self::getInstance();
        if($inst->getLevel() >= self::ERROR) $inst->getHandler()->write('error', $msg);
    }    
    
    /**
     * Log an sql query, only logged when level >= INFO
     * 
     * @param string $msg   The message to log
     */       
    public static function sql($msg)
    {     
        $inst = self::getInstance();
        if($inst->getLevel() >= self::INFO)
        {            
            if($inst->getLevel() === self::DEBUG) $inst->getHandler()->write('debug', '{Db->execute()} Call from '.$inst->getBacktrace(array('/db/')));
            $inst->getHandler()->write('sql', $msg);
        }
    }
}