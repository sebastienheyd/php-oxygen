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

class f_date_Format
{
    private static $_instances;
    private $_xml;
    private $_date;
    private $_vars;
    
    /**
     * Returns dateFormat instance
     * 
     * @return f_date_Format    Return singleton instance of f_date_Format
     */
    public static function getInstance($region = null)
    {
        if($region === null) $region = I18n::getLocale();
        if(!isset(self::$_instances[$region])) self::$_instances[$region] = new self($region);
        return self::$_instances[$region];
    }
    
    /**
     * f_date_Format class constructor
     */
    private function __construct($region)
    {        
        list($iso639, $iso3166) = preg_split('/[_-]/', $region);
        $folder = FW_DIR.DS.'date'.DS.'json';

        if(is_file($folder.DS.$region.'.js')) $json = $folder.DS.$region.'.json';
        if($json === null && is_file($folder.DS.$iso639.'.json')) $json = $folder.DS.$iso639.'.json';        
        if($json === null) $json = $json = $folder.DS.'en.json';

        $this->_vars = json_decode(file_get_contents($json), true);
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
     * @return string          The formated date with the ouput pattern
     */
    public function toFormat($format = 'Y-m-d H:i:s')
    {
        $result = '';
		$escaped = false;
		for ($i = 0 ; $i < strlen($format) ; $i++)
		{
			$c = substr($format, $i, 1);
			if ($c == '\\')
			{
				if ($escaped) $result .= '\\';
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
					// Day of the month, 1 digit
					case 'j' :
						$result .= $this->_date->getDay();
						break;
                    
					// Day of the month, 2 digits with leading zeros
					case 'd' :
						$result .= sprintf('%02d', $this->_date->getDay());
						break;

					// A textual representation of a day, three letters
					case 'D' :
						$result .= $this->_vars['dayNamesShort'][$this->_date->getDayOfWeek()];
						break;

					// A full textual representation of the day of the week
					case 'l' :
                        $result .= $this->_vars['dayNames'][$this->_date->getDayOfWeek()];
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
						$result .= $this->_date->getDayOfWeek();
						break;
                    
					// Numeric representation of a month, without leading zeros
					case 'n' :
						$result .= $this->_date->getMonth();
						break;

					// Numeric representation of a month, with leading zeros
					case 'm' :
						$result .= sprintf('%02d', $this->_date->getMonth());
						break;
                    
					// A short textual representation of a month, three letters
					case 'M' :
                        $result .= $this->_vars['monthNamesShort'][(int) $this->_date->getMonth() - 1];
						break;
                    
					// A full textual representation of a month, such as January or March
					case 'F' :
                        $result .= $this->_vars['monthNames'][(int) $this->_date->getMonth() - 1];
						break;

					// Number of days in the given month
					case 't' :
						$result .= $this->_date->getDaysInMonth();
						break;

					// The day of the year (starting from 0)
					case 'z' :
						$result .= $this->_date->getDayOfYear();
						break;

					// Whether it's a leap year
					case 'L' :
						$result .= (string) $this->_date->isLeapYear();
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

					// Difference to Greenwich time (GMT) in hours
					case 'O' :
						$result .= $this->_date->getGmtDiff();
						break;

					// 12-hour format of an hour without leading zeros
					case 'g' :
						$result .= strval($this->_date->getHour() % 12);
						break;

					// 24-hour format of an hour without leading zeros
					case 'G' :
						$result .= $this->_date->getHour();
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
     * @param string $format    [optional] Output format to use (shortDate, longDate, shortTime, fullDateTime, monthDay, yearMonth). Default is 'fullDateTime'
     * @return string           The formated date
     */
    public function toSmartFormat($format = 'fullDateTime')
    {
        return $this->toFormat($this->_vars['smartFormat'][$format]);
    }
    
    /**
     * Returns an array of differences in years, weeks, days, hours, minutes, seconds between current date and instanciated date
     * 
     * @return array 
     */
    public function getDiff($timestamp = null)
    {   
        // Init result
        $result = array();
        
        $d = $timestamp === null ? time() : $timestamp;
                
        // Get days diff
        $daysDiff = strtotime(date('d-m-Y', $this->_date->toTimeStamp())) - strtotime(date('d-m-Y', $d));        
        
        // Get hours diff
        $hoursDiff = strtotime(date('H:i:s', $this->_date->toTimeStamp())) - strtotime(date('H:i:s', $d));                            
                
        // Get position in time
        $position = $daysDiff >= 0 ? 'past' : 'future';        
        if($daysDiff == 0) $position = $hoursDiff >= 0 ? 'past' : 'future';
        $result['position'] = $position;
            
        // Get absolute values
        $daysDiff = abs($daysDiff);
        $hoursDiff = abs($hoursDiff);

        $leap = 0;
        if($daysDiff != 0) $leap = $this->_getNbLeapDays();            

        if($position === 'past') $daysDiff -= $leap > 0 ? ($leap-1) * 86400 : 0;                
        if($position === 'future') $daysDiff += $leap > 0 ? ($leap-1) * 86400 : 0;                

        $result['years'] = floor($daysDiff / 31536000);
        $daysDiff -= $result['years'] * 31536000;
        
        $result['months'] = floor($daysDiff / 2628000);
        $daysDiff -= $result['months'] * 2628000;

        
        $result['weeks'] = floor($daysDiff / 604800);
        $daysDiff -= $result['weeks'] * 604800;
        
        $result['days'] = floor($daysDiff / 86400);          
        
        $result['hours'] = floor($hoursDiff / 3600); 
        $hoursDiff -= $result['hours'] * 3600;
        
        $result['minutes'] = floor($hoursDiff / 60);
        $hoursDiff -= $result['minutes'] * 60;
        
        $result['seconds'] = floor($hoursDiff);
                               
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
    
    private function _getNbLeapDays()
    {
        $d1 = time();
        $d2 = $this->_date->toTimeStamp();
        
        $years = range(date('Y', time()), date('Y', $this->_date->toTimeStamp()));

        $leap = 0;
        $nb = count($years);
        
        foreach($years as $k => $year)
        {
            if(date('L', strtotime("$year-1-1")) === '1')
            {
                if($k === 0 || $k === $nb)
                {
                    $date1 = mktime(0, 0, 0, date("m", $d1), date("d", $d1), date("Y", $year)); 
                    $date2 = mktime(0, 0, 0, date("m", $d2), date("d", $d2), date("Y", $year)); 
                    $days = range(date('z', $date1), date('z', $date2));
                    if(in_array(59, $days)) $leap ++;
                }
                else
                {     
                    $leap++;
                }
            }
        }
        
        return $leap;
    }
    
    /**
     * Returns a string which indicates the difference between current date and instanciated date.
     * 
     * @example 1 year ago - 1 day 2 hours ago - etc...
     * 
     * @param integer $precision    [optional] Result's level precision (1 to 6). Default is 1
     * @param string $separator     [optional] Separator to use between results, default is ' ' (space)
     * @return string               Difference in full-text
     */
    public function toDiff($timestamp = null, $precision = 1, $separator = ' ', $futurePast = true)
    {
        $diff = $this->getDiff($timestamp);

        $diff['days'] = $diff['weeks'] * 7 + $diff['days'];        
        unset($diff['weeks']);        
        
        $times = array();
        foreach($diff as $k => $d) if(is_float($d) && $d > 0) $times[$k] = $d;       
        $times = array_slice($times, 0, $precision, true);

        if(empty($times)) return sprintf($this->_vars['relativeTime'][$position], $this->_formatDiff(1, 'seconds'));
        
        $res = array();
        foreach($times as $type => $value)
        {
            $res[] = $this->_formatDiff($value, $type);                            
        }    
        
        $res = join($separator, $res);
        
        if(!$futurePast) return $res;
        return sprintf($this->_vars['relativeTime'][$diff['position']], $res);
    }
    
    private function _formatDiff($value, $unit)
    {       
        if($unit == 'months') $unit = 'M';
        if($unit !== 'M') $unit = $unit[0];
        $str = '';
        
        if($value <= 1)
        {
            if(isset($this->_vars['relativeTime'][$unit]))
            {
                $str = $this->_vars['relativeTime'][$unit];                
            }
        }
        else
        {
            if(isset($this->_vars['relativeTime'][$unit.$unit]))
            {
                $str = sprintf($this->_vars['relativeTime'][$unit.$unit], $value);
            }
        }
        
        return $str;
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