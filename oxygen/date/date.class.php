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

class f_date_Date
{
	private $_date;
	private $_year;
	private $_month;
	private $_day;
	private $_hour;
	private $_minute;
	private $_second;    
    
    /**
     * Get a new date instance
     * 
     * @param string $date      The input date
     * @param string $format    [optional] The input date format. Default is 'Y-m-d H:i:s'
     * @return f_date_Date      Return a new instance of f_date_Date
     */
    public static function getInstance($date, $format = 'Y-m-d H:i:s')
    {
        $dateTokens   = preg_split("/[\s.,:\/-]+/", $date);
		$formatTokens = preg_split("/[\s.,:\/-]+/", $format);

        if(count($dateTokens) !== count($formatTokens))
        {
            throw new UnexpectedValueException('Given date and format has not the same number of tokens.');
        }    
        
		$y = date('Y');
		$m = date('m');
		$d = date('d');
		$h = $i = $s = '00';

		foreach ($formatTokens as $j => $token)
		{
			switch ($token)
			{
				case 'Y' :
					$y = str_pad($dateTokens[$j], 4, '0', STR_PAD_LEFT);
					break;
				case 'n' :
				case 'm' :
					$m = str_pad($dateTokens[$j], 2, '0', STR_PAD_LEFT);
					break;
                case 'M' :
                    $months = array(1 => 'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec');
                    $m = str_pad(array_search(ucfirst($dateTokens[$j]), $months),2, '0', STR_PAD_LEFT);
					break;
                case 'j' :
				case 'd' :
					$d = str_pad($dateTokens[$j], 2, '0', STR_PAD_LEFT);
					break;
				case 'h' :
                case 'G' :
				case 'H' :
					$h= str_pad($dateTokens[$j], 2, '0', STR_PAD_LEFT);
					break;
				case 'i' :
					$i = str_pad($dateTokens[$j], 2, '0', STR_PAD_LEFT);
					break;
				case 's' :
					$s = str_pad($dateTokens[$j], 2, '0', STR_PAD_LEFT);
					break;
			}
		}        

		return new self("$y-$m-$d $h:$i:$s");
    }
    
    /**
     * Get a new date instance
     * 
     * @param string $date      The date to instance. The format must be Y-m-d H:i:s
     */
    private function __construct($date)
    {
        if (!preg_match('#^(\\d{4})\-(\\d{1,2})\-(\\d{1,2})(\s+(\\d{1,2}):(\\d{1,2}):(\\d{1,2}))?$#', $date))
        {
            throw new InvalidArgumentException("Invalid date format : $date format must be Y-m-d h:i:s");
        }

        $this->_date = $date;
        
        $date = explode(' ', $date);

        list($this->_year, $this->_month, $this->_day) = explode('-', $date[0]);
        list($this->_hour, $this->_minute, $this->_second) = explode(':', $date[1]);

        if(!checkdate($this->_month, $this->_day, $this->_year))
        {
            throw new RangeException("Invalid date : ".$date." date does not exist");
        }
    }
    
    /**
     * Default string to return on printing
     * 
     * @return string   Instanciated date string 
     */
    public function __toString()
    {
        return $this->_date;
    }
    
    /**
     * Returns an array of values which indicates differences between current date and instanciated date.
     * 
     * @param integer   Timestamp to compare current date with
     * @return array    Array of differences 
     */
    public function getDiff($timestamp = null)
    {
        return f_date_Format::getInstance()->setDate($this)->getDiff($timestamp);
    }
    
    /**
     * Returns a string which indicates the difference between current date and instanciated date.
     * 
     * @example 1 year ago - 1 day 2 hours ago - etc...
     * 
     * @param integer               [optional] Timestamp to compare current date with
     * @param integer $precision    [optional] Result's level precision (1 to 6). Default is 1
     * @param string $separator     [optional] Separator to use between results, default is ' ' (space)
     * @param string $lang          [optional] iso-639-1 code (ex: fr, en, es, ...). Default is null. Will take I18n setted lang if null
     * @return string               Difference in full-text
     */
    public function toDiff($timestamp = null, $precision = 1, $separator = ' ', $futurePast = true, $langOrRegion = null)
    {
        return f_date_Format::getInstance($langOrRegion)->setDate($this)->toDiff($timestamp, $precision, $separator, $futurePast);
    }
    
    /**
     * Return current instanciated date to a formated string value.
     * 
     * @param string $format   [optional] Output format to use. Default is 'Y-m-d H:i:s'
     * @param string $lang     [optional] iso-639-1 code (ex: fr, en, es, ...). Default is null. Will take I18n setted lang if null
     * @return string          The formated date with the ouput pattern
     */
    public function toFormat($format = 'Y-m-d H:i:s', $langOrRegion = null)
    {        
        return f_date_Format::getInstance($langOrRegion)->setDate($this)->toFormat($format);
    }
        
    /**
     * Returns current instanciated date to a pre-formatted string value
     * 
     * @param string $format    [optional] Output format to use (fulltext-date-time, fulltext-date, date-time, year-month-day, day-month). Default is 'fulltext-date-time'
     * @param string $lang      [optional] iso-639-1 code (ex: fr, en, es, ...). Default is null. Will take I18n setted lang if null
     * @return string           The formated date
     */
    public function toSmartFormat($format = 'fullDateTime', $langOrRegion = null)
    {
        return f_date_Format::getInstance($langOrRegion)->setDate($this)->toSmartFormat($format);
    }
    
    /**
     * Returns current instanciated date in MySql datetime format
     * 
     * @return string   Current date in MySql datetime format
     */
    public function toMysql()
    {
        return f_date_Format::getInstance('en_US')->setDate($this)->toFormat('Y-m-d H:i:s');
    }    
    
    /**
     * Returns current instanciated date in standard HTTP format
     * 
     * @return string   Current date in standard HTTP format 
     */
    public function toHttp()
    {
        return f_date_Format::getInstance('en_US')->setDate($this)->toFormat('D, d M Y H:i:s O');
    }    
    
    /**
     * Returns current instanciated date timestamp
     * 
     * @return integer
     */
    public function toTimeStamp()
    {
        if(!isset($this->timestamp)) $this->timestamp = strtotime($this->_date);
        return $this->timestamp;
    }    	
	
    /**
     * Returns current instanciated date day of week
     * @return integer
     */
	public function getDayOfWeek()
	{
        if(!isset($this->dayOfWeek)) $this->dayOfWeek = date("w", $this->toTimeStamp());
		return $this->dayOfWeek;
	}	
	
    /**
     * Returns current instanciated date day in month
     * @return integer
     */    
	public function getDaysInMonth()
	{
        if(!isset($this->daysInMonth)) $this->daysInMonth = date("t", $this->_month);
		return $this->daysInMonth;
	}
	
    /**
     * Returns current instanciated date day of year
     * @return integer
     */    
	public function getDayOfYear()
	{
        if(!isset($this->dayOfYear)) $this->dayOfYear = date("z", $this->toTimeStamp());
		return $this->dayOfYear;
	}
	
    /**
     * Returns current instanciated date year is leap
     * @return boolean
     */    
	public function getGmtDiff()
	{
        if(!isset($this->gmtDiff)) $this->gmtDiff = date("O", $this->toTimeStamp());
		return $this->gmtDiff;
	}  
    
    /**
     * Returns year of instanciated date
     * @return integer
     */
	public function getYear()
	{
		return $this->_year;
	}	
    
    /**
     * Returns month of instanciated date
     * @return integer
     */
    public function getMonth()
	{
		return $this->_month;
	}	
		
    /**
     * Returns day of instanciated date
     * @return integer 
     */
	public function getDay()
	{
		return $this->_day;
	}
	
    /**
     * Returns hour of instanciated date
     * @return integer 
     */    
	public function getHour()
	{
		return $this->_hour;
	}	
	
    /**
     * Returns minute of instanciated date
     * @return integer 
     */    
	public function getMinute()
	{
		return $this->_minute;
	}	
	
    /**
     * Returns second of instanciated date
     * @return integer 
     */    
	public function getSecond()
	{
		return $this->_second;
	}    
}