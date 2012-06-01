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

class f_date_Format
{
    private static $_instance;
    private $_xml;
    private $_date;
    
    /**
     * Returns dateFormat instance
     * 
     * @return f_date_Format    Return singleton instance of f_date_Format
     */
    public static function getInstance()
    {
        if(!isset(self::$_instance)) self::$_instance = new self();
        return self::$_instance;
    }
    
    /**
     * f_date_Format class constructor
     */
    private function __construct()
    {
        $this->_xml = simplexml_load_file(dirname(__FILE__).DS.'xml'.DS.'date.xml');
    }
    
    /**
     * Sets the date to manage
     * 
     * @param f_date_Date $date     Input f_date_Date object
     * @return f_date_Format        Return current instance of f_date_Format
     */
    public function setDate(f_date_Date $date)
    {
        $this->_date = $date;
        return $this;
    }
    
    /**
     * Return current date to a formated string value.
     * 
     * @param string $format   [optional] Output format to use. Default is 'Y-m-d H:i:s'
     * @param string $lang     [optional] iso-639-1 code (ex: fr, en, es, ...). Default is null. Will take I18n setted lang if null
     * @return string          The formated date with the ouput pattern
     */
    public function toFormat($format = 'Y-m-d H:i:s', $lang = null)
    {
        $result = '';
		$escaped = false;
		for ($i = 0 ; $i < strlen($format) ; $i++)
		{
			$c = substr($format, $i, 1);
			if ($c == '\\')
			{
				if ($escaped)
				{
					$result .= '\\';
				}
				$escaped = ! $escaped;
			}
			else if ( $escaped )
			{
				$result .= $c;
				$escaped = false;
			}
			else
			{
				switch ($c)
				{
					// Day of the month, 2 digits with leading zeros
					case 'd' :
						$result .= sprintf('%02d', $this->_date->getDay());
						break;

					// A textual representation of a day, three letters
					case 'D' :
						$result .= $this->_loadLocale("abbr.".strtolower(date("l", $this->_date->toTimeStamp())), $lang);
						break;

					// Day of the month without leading zeros
					case 'j' :
						$result .= strval($this->_date->getDay());
						break;

					// A full textual representation of the day of the week
					case 'l' :
                        $result .= $this->_loadLocale(strtolower(date("l", $this->_date->toTimeStamp())), $lang);
						break;

					// English ordinal suffix for the day of the month, 2 characters
					case 'S' :
						if(isset(self::$englishOrdinalSuffixArray[strval($this->_date->getDay())]))
						{
							$result .= self::$englishOrdinalSuffixArray[strval($this->_date->getDay())];
						}
						else
						{
							$result .= self::$englishOrdinalSuffixArray['default'];
						}
						break;

					// Numeric representation of the day of the week
					case 'w' :
						$result .= strval($this->_date->getDayOfWeek());
						break;

					// A full textual representation of a month, such as January or March
					case 'F' :
                        $result .= $this->_loadLocale(strtolower(date("F", $this->_date->toTimeStamp())), $lang);
						break;

					// Numeric representation of a month, with leading zeros
					case 'm' :
						$result .= sprintf('%02d', $this->_date->getMonth());
						break;

					// A short textual representation of a month, three letters
					case 'M' :
                        $result .= $this->_loadLocale("abbr.".strtolower(date("F", $this->_date->toTimeStamp())), $lang);
						break;

					// Numeric representation of a month, without leading zeros
					case 'n' :
						$result .= strval($this->_date->getMonth());
						break;

					// Number of days in the given month
					case 't' :
						$result .= strval($this->_date->getDaysInMonth());
						break;

					// The day of the year (starting from 0)
					case 'z' :
						$result .= strval($this->_date->getDayOfYear());
						break;

					// Whether it's a leap year
					case 'L' :
						$result .= $this->_date->isLeapYear() ? '1' : '0';
						break;

					// A full numeric representation of a year, 4 digits
					case 'Y' :
						$result .= sprintf('%04d', $this->_date->getYear());
						break;

					// A two digit representation of a year
					case 'y' :
						$result .= substr(strval($this->_date->getYear()), -2);
						break;

					// Lowercase Ante meridiem and Post meridiem
					case 'a' :
						$result .= ($this->_date->getHour() < 12) ? 'am' : 'pm';
						break;

					// Uppercase Ante meridiem and Post meridiem
					case 'A' :
						$result .= ($this->_date->getHour() < 12) ? 'AM' : 'PM';
						break;

					// 12-hour format of an hour without leading zeros
					case 'g' :
						$result .= strval($this->_date->getHour() % 12);
						break;

					// 24-hour format of an hour without leading zeros
					case 'G' :
						$result .= strval($this->_date->getHour());
						break;

					// 12-hour format of an hour with leading zeros
					case 'h' :
						$result .= sprintf('%02d', $this->_date->getHour() % 12);
						break;

					// 24-hour format of an hour with leading zeros
					case 'H' :
						$result .= sprintf('%02d', $this->_date->getHour());
						break;

					// Minutes with leading zeros
					case 'i' :
						$result .= sprintf('%02d', $this->_date->getMinute());
						break;

					// Seconds with leading zeros
					case 's' :
						$result .= sprintf('%02d', $this->_date->getSecond());
						break;

					default :
						$result .= $c;
				}
			}
		}
		return $result;
    }
    
    /**
     * Returns current instanciated date to a pre-formatted string value
     * 
     * @param string $format    [optional] Output format to use (fulltext-date-time, fulltext-date, date-time, year-month-day, day-month). Default is 'fulltext-date-time'
     * @param string $lang      [optional] iso-639-1 code (ex: fr, en, es, ...). Default is null. Will take I18n setted lang if null
     * @return string           The formated date
     */
    public function toSmartFormat($format = 'fulltext-date-time', $lang = null)
    {
        if($lang === null) $lang = I18n::getLang();
        
        $lang = strtolower($lang);        
        
        $format = $this->_loadLocale($format, $lang);                
        return $this->toFormat($format, $lang);
    }
    
    /**
     * Returns an array of differences in years, weeks, days, hours, minutes, seconds between current date and instanciated date
     * 
     * @return array 
     */
    public function getDiff()
    {                   
        $time = strtotime(date('d-m-Y', time())) - strtotime(date('d-m-Y', $this->_date->toTimeStamp()));
        $time2 = strtotime(date('H:i:s', time())) - strtotime(date('H:i:s', $this->_date->toTimeStamp()));                            
        
        $result = array();
        
        $position = $time >= 0 ? 'past' : 'future';        
        
        $time = abs($time);

        $yStart = date('Y', time());        
        $yEnd = date('Y', $this->_date->toTimeStamp());
        $years = range($yStart, $yEnd);

        $leap = 0;
        foreach($years as $year)
        {
            if(date('L', strtotime("$year-01-01")) == 1) $leap++;
        }
        
        $time -= $leap > 0 ? ($leap-1) * 86400 : 0;        

        $result['years'] = floor($time / 31536000);
        $time -= $result['years'] * 31536000;
        
        $result['months'] = floor($time / 2628000);
        $time -= $result['months'] * 2628000;

        $result['weeks'] = floor($time / 604800);
        $time -= $result['weeks'] * 604800;
        
        $result['days'] = floor($time / 86400);          
        
        $result['hours'] = floor($time2 / 3600); 
        $time2 -= $result['hours'] * 3600;
        
        $result['minutes'] = floor($time2 / 60);
        $time2 -= $result['minutes'] * 60;
        
        $result['seconds'] = floor($time2);
               
        $result['position'] = $position;
        
        if($result['hours'] < 0) 
        {
            if($result['days'] == 0)
            {
                $result['weeks'] = $result['weeks'] - 1;
                $result['days'] = 6;
                $result['hours'] = 24 - abs($result['hours']);  
            }
            else
            {
                $result['days'] = $result['days']-1;
                $result['hours'] = 24 - abs($result['hours']);                
            }
        }      
        
        return $result;
    }    
    
    /**
     * Returns a string which indicates the difference between current date and instanciated date.
     * 
     * @example 1 year ago - 1 day 2 hours ago - etc...
     * 
     * @param integer $precision    [optional] Result's level precision (1 to 6). Default is 1
     * @param string $separator     [optional] Separator to use between results, default is ' ' (space)
     * @param string $lang          [optional] iso-639-1 code (ex: fr, en, es, ...). Default is null. Will take I18n setted lang if null
     * @return string               Difference in full-text
     */
    public function toDiff($precision = 1, $separator = ' ', $lang = null)
    {
        $diff = $this->getDiff();

        $times = array();
        foreach($diff as $k => $d)
        {         
            if($d > 0) $times[$k] = $d;
        }    
        
        
        $times = array_slice($times, 0, $precision, true);
        
        
        if(empty($times))
        {
            return $this->_loadLocale('now', $lang);
        }
        else
        {
            $res = array();
            foreach($times as $type => $value)
            {
                if($value == 1) $type = substr($type, 0, -1);
                $res[] = $this->_loadLocale($type, $lang, array('time' => $value));
            }    
            return $this->_loadLocale($diff['position'], $lang, array('time' => join($separator, $res)));
        }
    }        
    
    /**
     * Load locale value from date.xml
     * 
     * @param string $key       Key to translate
     * @param string $lang      [optional] iso-639-1 code (ex: fr, en, es, ...). Default is null. Will take I18n setted lang if null
     * @param array $replace    [optionl] Associative array of value(s) to replace in locale string
     * @return string           The localized string
     */
    private function _loadLocale($key, $lang = null, $replace = array())
    {
        if($lang === null) $lang = I18n::getLang();
        
        $lang = strtolower($lang);
        
        $locale = $this->_xml->xpath('//locale[@key="'.$key.'"]/content[@lang="'.$lang.'"]');
        
        if(empty($locale) && $lang != 'en') return $this->_loadLocale($key, 'en');
        if(empty($locale)) trigger_error ('Locale not found : '.$key, E_USER_ERROR);               
        $result = (string) $locale[0];
        
        if(!empty($replace))
        {
            foreach ($replace as $k => $v)
            {
                $result = str_replace('{'.$k.'}', $v, $result);
            }    
        }    
        
        return $result;
    }
    
    /**
     * Array of english ordinal suffix for a given day
     * Used by toFormat method
     * 
     * @var string 
     */
	private static $englishOrdinalSuffixArray = array (
		'1'  => 'st',
		'21' => 'st',
		'31' => 'st',
		'2'  => 'nd',
		'22' => 'nd',
		'3'  => 'rd',
		'13' => 'rd',
		'23' => 'rd',
		'default' => 'th'
		);	    
}