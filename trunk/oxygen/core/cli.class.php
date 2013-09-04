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
class Cli
{

    private static $_instance;
    private $_argc;
    private $_argv;

    /**
     * @return Cli
     */
    public static function getInstance()
    {
        if ((defined('CLI_MODE') && CLI_MODE === false) || !self::isCurrentMode())
            return false;
        if (!isset(self::$_instance))
            self::$_instance = new self();
        return self::$_instance;
    }

    /**
     * Main constructor
     */
    public function __construct()
    {
        global $argv, $argc;
        $this->_argv = $argv;
        $this->_argc = $argc;
    }

    /**
     * Check if current mode is cli
     * 
     * @return boolean
     */
    public static function isCurrentMode()
    {
        return PHP_SAPI === 'cli';
    }

    /**
     * Get cli valid text color
     * 
     * @param string $color 
     */
    public function getColor($color = null)
    {
        $colors = array('0;30' => 'black',
            '0;31' => 'red',
            '0;32' => 'green',
            '0;33' => 'brown',
            '0;34' => 'blue',
            '0;35' => 'purple',
            '0;36' => 'cyan',
            '0;37' => 'light gray',
            '1;30' => 'dark gray',
            '1;31' => 'light red',
            '1;32' => 'light green',
            '1;33' => 'yellow',
            '1;34' => 'light blue',
            '1;35' => 'light purple',
            '1;36' => 'light cyan',
            '1;37' => 'white'
        );

        if ($color !== null && $k = array_search($color, $colors))
            return "\033[" . $k . "m";
        return "\033[00m";
    }

    /**
     * Set cli valid text color
     * 
     * @param string $color 
     */
    public function setColor($color = null)
    {
        echo $this->getColor($color);
    }

    /**
     * Get a colored string in cli mode
     * 
     * @param string $string    The string to print
     * @param string $color     The color to use (see array below to get options)
     */
    public function getString($string, $color = null)
    {
        return $this->getColor($color) . $string . $this->getColor();
    }

    /**
     * Print a colored string in cli mode
     * 
     * @param string $string    The string to print
     * @param string $color     The color to use (see array below to get options)
     */
    public function printf($string, $color = null)
    {
        echo $this->getString($string, $color);
    }

    /**
     * Ask a question who require a confirmation (y/n)
     * 
     * @param string $question
     * @return boolean
     */
    public function confirm($question, $values = array())
    {
        if (empty($values))
            $values = array('y' => true, 'n' => false);
        if (count($values) <= 4)
            $opts = ' (' . join('/', array_keys($values)) . ')';
        $test = strtolower($this->input($question . $opts . ' : '));
        if (array_key_exists($test, $values))
            return $values[$test];
        return $this->confirm($question);
    }

    /**
     * Display a meter with percentage like "34/100 (34%)"
     * 
     * @param integer $current      The current iterator
     * @param integer $max          The max iterator number
     * @param string $prefix        [optional] Prefix the meter with this string
     */
    public function meter($current, $max, $prefix = '')
    {
        $str = $prefix . $current . '/' . $max . ' (' . ceil(($current / $max) * 100) . '%)';
        $l = strlen($str);
        for ($i = 0; $i <= $l; $i++)
            echo "\010";
        echo $str;
    }

    /**
     * Input a string in CLI
     * 
     * @param string $label     Label of the question
     * @return string           Answer of the question
     */
    public function input($label)
    {
        $this->printf($label);
        return trim(fgets(STDIN));
    }

    /**
     * Get argument from command
     * 
     * @return string|false       Argument value or null if none
     */
    public function getArg($argNum)
    {
        return isset($this->_argv[$argNum]) ? $this->_argv[$argNum] : false;
    }

    /**
     * Return all passed arguments
     * 
     * @return array
     */
    public function getArgs()
    {
        return $this->_argv;
    }

    /**
     * Check if given argument is present
     * 
     * @param string $arg       The argument name to check
     * @return boolean          Return true if present
     */
    public function hasArg($arg)
    {
        // Only one argument (= script name) return false
        if ($this->_argc == 1 || !is_string($arg))
            return false;
        return array_search($arg, $this->_argv);
    }

    /**
     * Clears the screen
     */
    public function clearScreen()
    {
        $clearscreen = chr(27) . "[H" . chr(27) . "[2J";
        print $clearscreen;
    }
    
    /**
     * Download a file and show progression, require cURL
     * 
     * @param string $href          File to download
     * @param string $destFile      Destination file
     */
    public function download($href, $destFile)
    {
        $fp = fopen($destFile, 'w');
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $href);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_PROGRESSFUNCTION, array('cli', '_progress'));
        curl_setopt($ch, CURLOPT_NOPROGRESS, false);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_FILE, $fp);
        $data = curl_exec($ch);
        curl_close($ch);
        fclose($fp);
        echo PHP_EOL;
    }

    /**
     * cURL download progression function required by download method
     * 
     * @param integer $download_size
     * @param integer $downloaded
     * @param integer $upload_size
     * @param integer $uploaded
     */
    private function _progress($download_size, $downloaded, $upload_size, $uploaded)
    {
        $str = '';
        if($download_size > 0) $str = $downloaded.'kb / '.$download_size.'kb ('.round($downloaded / $download_size  * 100).' %)';
        $l = strlen($str);
        for ($i = 0; $i <= $l; $i++) echo "\010";
        echo $str;
    }    

}