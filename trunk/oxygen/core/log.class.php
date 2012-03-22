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
    
    const ALL   = 30;
    const DEBUG = 20;
    const ERROR = 10;
    const OFF   = 0;
    
    private $_level;
    
    /**
     * @return Log 
     */
    public static function getInstance()
    {
        if(is_null(self::$_instance)) self::$_instance = new self();
        return self::$_instance;
    }
    
    /**
     * Constructor 
     */
    private function __construct()
    {
        $level = strtoupper(Config::get('debug', 'logging_level', 'OFF'));
        $authorized = array('OFF', 'ERROR', 'DEBUG', 'ALL');
        
        if(!in_array($level, $authorized)) $level = 'OFF';
        
        $this->_level = constant("self::".$level);
        
        if($this->_level > 0)
        {
            if(!is_dir(LOGS_DIR)) mkdir(LOGS_DIR, 0775);
            if(!is_writable(LOGS_DIR)) trigger_error('Log folder is not writeable');            
        }
    }
    
    /**
     * Get the current logging level
     * @return integer      See Log constants 
     */
    public function getLevel()
    {
        return $this->_level;
    }
    
    /**
     * Write a message to a log file
     * 
     * @param string $fileName      Log file name without .log extension
     * @param string $msg           The message to log        
     * @return integer|false        Return bytes written or false on error
     */
    public function write($fileName, $msg)
    {
        $logFile = LOGS_DIR.DS.$fileName;
        
        // If logfile is more than 10 megas, backup and create a new file
        if(is_file($logFile) && filesize($logFile) >= 10240000)
		{
			$nbFiles = count(glob($logFile.'.*'));
			$nbFiles = str_pad($nbFiles, 3, '0', STR_PAD_LEFT);			
			if(copy($logFile, $logFile.".".$nbFiles.".log")) unlink($logFile);
		}
        
        // already write into allinone.log file
        if($fileName != 'allinone.log') $this->write('allinone.log', $msg);
        
        return file_put_contents($logFile, $msg, FILE_APPEND | LOCK_EX);
    }
    
    /**
     * Format a string to a log format string
     * 
     * @param string $msg       The message to log
     * @param string $type      Log type (3 chars)
     * @param string $time      Time to log (for db execution per example)
     * @return string           The formatted string to write in log
     */
    public function formatMsg($msg, $type = '---', $time = null)
    {
        if(mb_strlen($type) > 3) $type = substr($type, 0, 3);
        if(is_null($time)) $time = '-------';
        
        $time = str_pad($time, 7, '0', STR_PAD_RIGHT);
        
        return '['.date('Y-m-d H:i:s').'] ['.$_SERVER['REMOTE_ADDR'].'] ['.strtoupper($type).'] ['.$time.'] '.$msg.PHP_EOL;
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
            if(strstr($trace['class'], 'Log')) continue;
            if(preg_match('#'.join('|', $exclude).'#', $trace['file'])) continue;
            return $trace['file'].' (ln.'. $trace['line'].')';                
        }
    }
    
    /**
     * Log an information, only logged when level = ALL
     * 
     * @param string $msg   The message to log
     * @param string $time  Time to log (for db execution per example)
     */
    public static function info($msg, $time = null)
    {
        $inst = self::getInstance();
        
        $level = $inst->getLevel();
        
        if($level >= self::ALL)
        {
            $msg = $inst->formatMsg($msg, 'inf', $time);
            $inst->write('info.log', $msg);       
        }
    }     
    
    /**
     * Log a debug information, only logged when level >= DEBUG
     * 
     * @param string $msg   The message to log
     * @param string $time  Time to log (for db execution per example)
     */    
    public static function debug($msg, $time = null)
    {
        $inst = self::getInstance();
        
        $level = $inst->getLevel();
        
        if($level >= self::DEBUG)
        {
            $msg = $inst->formatMsg($msg, 'dbg', $time);
            $inst->write('debug.log', $msg);       
        }
    } 
    
    /**
     * Log an error event, only logged when level >= ERROR
     * 
     * @param string $msg   The message to log
     * @param string $time  Time to log (for db execution per example)
     */     
    public static function error($msg, $time = null)
    {
        $inst = self::getInstance();
        
        $level = $inst->getLevel();
        
        if($level >= self::ERROR)
        {
            $msg = $inst->formatMsg($msg, 'err', $time);
            $inst->write('error.log', $msg);       
        }
    }    
    
    /**
     * Log an sql query, only logged when level >= DEBUG
     * 
     * @param string $msg   The message to log
     * @param string $time  Time to log (for db execution per example)
     */       
    public static function sql($msg, $time = null)
    {        
        $inst = self::getInstance();
        
        $level = $inst->getLevel();
        
        if($level >= self::DEBUG)
        {
            if($level == self::ALL)
            {
                $msg1 = $inst->getBacktrace(array('/db/'));
                $msg1 = $inst->formatMsg($msg1, 'sql');
                $inst->write('sql.log', $msg1);                    
            }

            $msg2 = $inst->formatMsg($msg, 'sql', $time);
            $inst->write('sql.log', $msg2);            
        }
    }
}