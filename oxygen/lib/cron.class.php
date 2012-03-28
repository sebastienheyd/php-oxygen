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

class Cron
{
    private $_exeMinute;
    private $_exeHour;
    private $_exeDayOfMonth;
    private $_exeMonth;
    private $_exeDayOfWeek;

    /**
     * Get a new instance
     * 
     * @return Cron     Return an new instance of Cron
     */
    public static function getInstance()
    {
        return new self();
    }

    /**
     * Check if task must be run at the given job time
     *
     * @example Cron::job('5 * * * 1-5')
     * 
     * @param string $time      Job in crontab style
     * @return boolean          Return true if given job is valid now
     */
    public static function job($time)
    {
        $c = new self();
        return $c->setJob($time)->execute();
    }

    /**
     * Set job time
     *
     * @param string $time      Job in crontab style
     * @return Cron             Return current instance of Cron
     */
    public function setJob($time)
    {
        $shortCuts = array('@yearly' => '0 0 1 1 *', '@annualy' => '0 0 1 1 *', '@monthly' => '0 0 1 * *', '@weekly' => '0 0 * * 0', '@daily' => '0 0 * * *', '@midnight' => '0 0 * * *', '@hourly' => '0 * * * *');

        if (isset($shortCuts[$time])) $time = $shortCuts[$time];

        if (!preg_match('/^([0-9\/\*\-,]+)? ([0-9\/\*\-,]+)? ([0-9\/\*\-,]+)? ([0-9\/\*\-,]+)? ([0-7\/\*\-,]+)?$/', $time)) throw new InvalidArgumentException('Job is not well formatted (' . $time . ')');

        list($this->_exeMinute, $this->_exeHour, $this->_exeDayOfMonth, $this->_exeMonth, $this->_exeDayOfWeek) = explode(' ', $time);
        return $this;
    }

    /**
     * Check if the given job time is running or not
     *
     * @return boolean          Return true if current job is valid now
     */
    public function execute()
    {
        if($this->_exeDayOfWeek == 7) $this->_exeDayOfWeek = 0;
        
        $minutes = $this->_parseFormat(0, 59, $this->_exeMinute);
        $hours = $this->_parseFormat(0, 23, $this->_exeHour);
        $daysOfMonth = $this->_parseFormat(1, 31, $this->_exeDayOfMonth);
        $months = $this->_parseFormat(1, 12, $this->_exeMonth);
        $daysOfWeek = $this->_parseFormat(0, 6, $this->_exeDayOfWeek); // 0 (sunday) to 6 (saturday)

        list($minute, $hour, $dayOfMonth, $month, $dayofweek) = explode(' ', date('i G j n w'));

        $minute = $minute[0] == '0' ? $minute[1] : $minute;

        return ($minutes[$minute] && $hours[$hour] && $daysOfMonth[$dayOfMonth] && $months[$month] && $daysOfWeek[$dayofweek]);
    }

    /**
     * Generic time parser by format
     * 
     * @param int $min          Time period minimum value
     * @param int $max          Time period maximum value
     * @param string $interval  Value or interval to check
     * @return array            Return time period array with boolean results
     */
    private function _parseFormat($min, $max, $interval)
    {
        $result = array();

        // interval is * set all to true
        if ($interval == '*')
        {
            for ($i = $min; $i <= $max; $i++) $result[$i] = true;
            return $result;
        }
        else
        {
            // or set all to false
            for ($i = $min; $i <= $max; $i++) $result[$i] = false;
        }

        // explode for multiple time definitions
        $interval = explode(',', $interval);

        // foreach time definition
        foreach ($interval as $val)
        {
            $every = null;

            // job must be run every ( ex : */5 or 10-20/2 )
            if (strstr($val, '/'))
            {
                $tmp = explode('/', $val);
                $between = $tmp[0];

                if ($between != '*' && !preg_match('/^([0-9]+)\-([0-9]+)$/', $between)) throw new UnexpectedValueException('Job interval is not correctly formatted');

                $every = $tmp[1];
            }

            // job's interval
            $val = explode('-', $val);

            // checks for outranged values
            if (isset($val[1]) && ($val[1] < $min || $val[1] > $max))
            {
                throw new RangeException('Value is not in range (' . $min . ' < ' . $val[1] . ' < ' . $max . ')');
            }
            else
            {
                if ($every !== null && $every != '*')
                {
                    if ($every < $min || $every > $max) throw new RangeException('Value is not in range (' . $min . ' < ' . $every . ' < ' . $max . ')');
                }
                else
                {
                    if ($val[0] < $min || $val[0] > $max) throw new RangeException('Value is not in range (' . $min . ' < ' . $val[0] . ' < ' . $max . ')');
                }
            }

            if (isset($val[0]) && isset($val[1]) && $every === null)
            {
                if ($val[0] <= $val[1])
                {
                    for ($i = $val[0]; $i <= $val[1]; $i++) $result[$i] = true; /* ex : 9-12 = 9, 10, 11, 12 */
                }
                else
                {
                    for ($i = $val[0]; $i <= $max; $i++) $result[$i] = true; /* ex : 10-4 = 10, 11, 12... */
                    for ($i = $min; $i <= $val[1]; $i++) $result[$i] = true; /* ... 1, 2, 3, 4 */
                }
            }
            else
            {
                if ($every !== null)
                {
                    // ex : */5
                    if ($between == '*')
                    {
                        for ($i = $min; $i <= $max; $i++)
                        {
                            if ($i % $every == 0) $result[$i] = true;
                        }
                    }
                    else
                    {
                        // ex : 10-20/2
                        list($rmin, $rmax) = explode('-', $between);
                        for ($i = $rmin; $i <= $rmax; $i++)
                        {
                            if ($i % $every == 0) $result[$i] = true;
                        }
                    }
                }
                else
                {
                    // value is a single integer
                    $result[$val[0]] = true;
                }
            }
        }
        return $result;
    }
}