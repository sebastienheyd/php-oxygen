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

class Date
{
    /**
     * Instanciate Date from a unix timestamp
     * 
     * @param integer $timestamp    Input unix timestamp
     * @return f_date_Date          Return an instance of f_date_Date
     */
    public static function fromTimeStamp($timestamp)
    {        
        return f_date_Date::getInstance(date('Y-m-d H:i:s', $timestamp));
    }
    
    /**
     * Instanciate Date with a sql formated date. 
     * 
     * @param string $date  Input date in MySQL format. Will autodetect date, datetime or timestamp formats
     * @return f_date_Date  Return an instance of f_date_Date
     */
    public static function fromMySql($date)
    {
        if(preg_match('/^([0-9]{4})-([0-9]{2})-([0-9]{2})$/', $date))
        {
            return self::fromFormat($date, 'Y-m-d');
        }
        else if(preg_match('/^([0-9]{4})-([0-9]{2})-([0-9]{2})\s([0-9]{2}):([0-9]{2}):([0-9]{2})$/', $date))
        {
            return self::fromFormat($date);
        }
        else if(preg_match('/^([0-9]+)$/', $date))
        {
            return self::fromTimeStamp($date);
        }
        throw new InvalidArgumentException('Sql date is not well formatted');
    }
    
    /**
     * Instanciate date with a user formated date
     * 
     * @param string $date      The input date
     * @param string $format    [optional] The input date format. Default is 'Y-m-d H:i:s'
     * @return f_date_Date      Return an instance of f_date_Date
     */
    public static function fromFormat($date, $format = 'Y-m-d H:i:s')
    {
        return f_date_Date::getInstance($date, $format);
    }
    
    /**
     * Instanciate Date with the current time
     * 
     * @return f_date_Date      Return an instance of f_date_Date
     */
    public static function now()
    {
        return f_date_Date::getInstance(date('Y-m-d H:i:s'));
    }
}
