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

class f_log_File implements f_log_Interface
{
    private static $_instance;
    
    /**
     * @return f_log_File 
     */
    public static function getInstance()
    {
        if(!isset(self::$_instance)) self::$_instance = new self();
        return self::$_instance;  
    }
    
    public function __construct()
    {
        if(!is_dir(LOGS_DIR)) mkdir(LOGS_DIR, 0775);
    }
        
    public function write($type, $msg)
    {
        if($type != 'allinone') $msg = $this->_formatMsg($msg, $type);
        
        $logFile = LOGS_DIR.DS.$type.'.log';    

        if(is_file($logFile) && filesize($logFile) >= Config::get('debug', 'max_log_size', '10') * 1024 * 1024)
		{
			if($this->_saveToFile($logFile, $logFile.".".date('Ymd_His').".log"))
            {
                $files = glob($logFile.'.*');

                $max_backups = Config::get('debug', 'max_log_backups', '5');
                
                if(!empty($files) && count($files) > $max_backups)
                {
                    rsort($files);
                    $files = array_slice($files, $max_backups);                    
                    foreach($files as $file) unlink($file);
                }
            }
            
            unlink($logFile);
		}
        
        // already write into allinone.log file
        if($type != 'allinone') $this->write('allinone', $msg);
        
        return file_put_contents($logFile, $msg, FILE_APPEND | LOCK_EX) > 0;
    }   
    
    /**
     * Save a backup from the current log file with rotation
     * 
     * @param string $logFile       File name of the current log file
     * @param string $backupFile    File name of the destination log file
     * @return bool                 Return true on success 
     */
    private function _saveToFile($logFile, $backupFile)
    {
        if(function_exists('gzencode')) return $this->_compressFile($logFile, $backupFile);
        return copy($logFile, $backupFile);
    }
    
    /**
     * Compress log file into a .gz file
     * 
     * @param string $logFile       File name of the current log file
     * @param string $backupFile    File name of the destination log file
     * @param intefer $level        [optional] Compression level, default is 9
     * @return boolean              Return true on success
     */
    private function _compressFile($logFile, $backupFile, $level = 9)
    {
        $dest = $backupFile . '.gz';
        $mode = 'wb' . $level;
        $success = false;

        if ($fp_out = gzopen($dest, $mode))
        {
            if($fp_in = fopen($logFile, 'rb'))
            {
                while (!feof($fp_in)) gzwrite($fp_out, fread($fp_in, 1024 * 512));
                fclose($fp_in);
                $success = true;
            }

            gzclose($fp_out);
        }
        
        return $success;
    } 
    
    /**
     * Format a string to a log format string
     * 
     * @param string $msg       The message to log
     * @param string $type      Log type (3 chars)
     * @param string $time      Time to log (for db execution per example)
     * @return string           The formatted string to write in log
     */
    private function _formatMsg($msg, $type = '---')
    {        
        if(mb_strlen($type) > 3) $type = substr($type, 0, 3);
                
        $msg = str_replace(PHP_EOL, ' ', $msg);
        $msg = preg_replace('#\s+#i', ' ', $msg);
        
        $log[] = strtoupper($type);
        $log[] = date(DATETIME_SQL);
        $log[] = $_SERVER['REMOTE_ADDR'];
        $log[] = trim($msg);
        
        return join(' | ', $log).PHP_EOL;
    }
        
}