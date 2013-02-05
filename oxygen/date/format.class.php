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
        if ($region === null) $region = I18n::getLocale();
        if (!isset(self::$_instances[$region])) self::$_instances[$region] = new self($region);
        return self::$_instances[$region];
    }

    /**
     * f_date_Format class constructor
     */
    private function __construct($region)
    {
        $folder = FW_DIR . DS . 'date' . DS . 'json';
        
        // trying to get file with full region code
        if (is_file($folder . DS . $region . '.json'))
            $json = $folder . DS . $region . '.json';

        // if file is not found
        if (!isset($json))
        {
            // get the lang iso
            $isos = preg_split('/[_-]/', $region);

            // trying to get file
            if (is_file($folder . DS . $isos[0] . '.json'))
                $json = $folder . DS . $isos[0] . '.json';

            // else get en file
            if (!isset($json))
                $json = $json = $folder . DS . 'en.json';
        }

        // decode json to array
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
     * Returns current instanciated date timestamp
     * 
     * @return integer
     */
    public function toTimeStamp()
    {
        return $this->_date->toTimeStamp();
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
        for ($i = 0; $i < strlen($format); $i++)
        {
            $c = substr($format, $i, 1);
            if ($c === '\\')
            {
                if ($escaped)
                    $result .= '\\';
                $escaped = !$escaped;
            }
            else if ($escaped)
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
                        if (isset(self::$englishOrdinalSuffixArray[strval($this->_date->getDay())]))
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
        $r = array();
        
        // Get current date DateTime object
        $_date1 = $this->_date->getDateTime();
                
        // Get given or current timestamp
        $d = $timestamp === null ? time() : $timestamp;        
        $_date2 = new DateTime("@".$d);        
        $_date2->setTimezone(new DateTimeZone(Config::get('general.timezone', @date_default_timezone_get())));                    
        
        // Catch position in time
        $diff = (int) $_date1->format('U') - (int) $_date2->format('U'); 
        $date1 = $_date1; $date2 = $_date2;
        $position = 'past';
        if($diff > 0) 
        {
            $position = 'future';
            $date1 = $_date2; $date2 = $_date1;
        }
        
        $r = array('years' => 0, 'months' => 0, 'weeks' => 0, 'days' => 0, 'hours' => 0, 'minutes' => 0, 'seconds' => 0, 'position' => $position);        
        
        // Set seconds
        $r['seconds'] = ((int) $date2->format('s')) - ((int) $date1->format('s'));
        if($r['seconds'] < 0) 
        {
            $r['minutes'] -= 1;
            $r['seconds'] = $r['seconds'] + 60;
        }
        
        // Set minutes
        $r['minutes'] = ((int) $date2->format('i')) - ((int) $date1->format('i')) + $r['minutes'];
        if($r['minutes'] < 0)
        {
            $r['hours'] -= 1;
            $r['minutes'] = $r['minutes'] + 60;
        }
        
        // Set hours
        $r['hours'] = ((int) $date2->format('G')) - ((int) $date1->format('G')) + $r['hours'];
        if($r['hours'] < 0) 
        {
            $r['days'] -= 1;
            $r['hours'] = $r['hours'] + 24;
        }
        
        // Set days
        $r['days'] = ((int) $date2->format('j')) - ((int) $date1->format('j')) + $r['days'];
        if($r['days'] < 0) 
        {
            $r['months'] -= 1;
            $nbDays = (int) $date1->format('t');
            if($position === 'future') $nbDays = $this->daysInMonth($date2->format('n') - 1, $date2->format('Y'));
            $r['days'] = $r['days'] + $nbDays;
        }
        
        // Set months
        $r['months'] = ((int) $date2->format('n')) - ((int) $date1->format('n')) + $r['months'];
        if($r['months'] < 0) 
        {
            $r['years'] -= 1;
            $r['months'] = $r['months'] + 12;
        }          
        
        // Set years
        $r['years'] = ((int) $date2->format('Y')) - ((int) $date1->format('Y')) + $r['years'];                
        
        // Set weeks / days
        $r['weeks'] = (int) floor($r['days'] / 7);
        $r['days'] = $r['days'] - ($r['weeks'] * 7);         
    
        return $r;
    }
    
    private function daysInMonth($month, $year)
    {
        return $month == 2 ? ($year % 4 ? 28 : ($year % 100 ? 29 : ($year % 400 ? 28 : 29))) : (($month - 1) % 7 % 2 ? 30 : 31);
    } 

    /**
     * Returns a string which indicates the difference between current date and instanciated date.
     * 
     * @example 1 year ago - 1 day 2 hours ago - etc...
     * 
     * @param integer $precision    [optional] Result's level precision (1 to 6). Default is 1
     * @param string $separator     [optional] Separator to use between results, default is ' ' (space)
     * @param boolean $futurePast   [optional] Display ago/in suffix/prefix. Default is true
     * @param integer $timestamp    [optional] Timestamp to use for diff. Default is current timestamp.
     * @return string               Difference in full-text
     */
    public function toDiff($precision = 1, $separator = ' ', $futurePast = true, $timestamp = null)
    {
        $diff = $this->getDiff($timestamp);

        $diff['days'] = $diff['weeks'] * 7 + $diff['days'];
        unset($diff['weeks']);
        
        $times = array();
        foreach ($diff as $k => $d) if (is_int($d) && $d > 0) $times[$k] = $d;
        $times = array_slice($times, 0, $precision, true);        
        
        if (empty($times)) return sprintf($this->_vars['relativeTime']['past'], $this->_formatDiff(1, 'seconds'));

        $res = array();
        foreach ($times as $type => $value) $res[] = $this->_formatDiff($value, $type);

        $res = join($separator, $res);

        if (!$futurePast) return $res;
        return sprintf($this->_vars['relativeTime'][$diff['position']], $res);
    }

    /**
     * Format a integer value to a full text value
     * 
     * @param integer $value    Value to use
     * @param string $unit      Unit to use
     * @return string
     */
    private function _formatDiff($value, $unit)
    {
        if ($unit === 'months') $unit = 'M';
        if ($unit !== 'M')      $unit = $unit[0];

        if ($value <= 1)
        {
            if (isset($this->_vars['relativeTime'][$unit]))  
                return $this->_vars['relativeTime'][$unit];
        }
        else
        {
            if (isset($this->_vars['relativeTime'][$unit . $unit]))
                return sprintf($this->_vars['relativeTime'][$unit . $unit], $value);
        }
    }

    /**
     * Array of english ordinal suffix for a given day
     * Used by toFormat method
     * 
     * @var string 
     */
    private static $englishOrdinalSuffixArray = array(
        '1' => 'st',
        '21' => 'st',
        '31' => 'st',
        '2' => 'nd',
        '22' => 'nd',
        '3' => 'rd',
        '13' => 'rd',
        '23' => 'rd',
        'default' => 'th'
    );

}