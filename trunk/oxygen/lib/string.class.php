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

class String
{

    /**
     * Check if given ip is well formated
     * 
     * @param string $ip    The IP address to check
     * @return boolean      Return true if the ip is correctly formated
     */
    public static function checkIp($ip)
    {
        return preg_match("/^([1-9]|[1-9][0-9]|1[0-9][0-9]|2[0-4][0-9]|25[0-5])" . "(\.([0-9]|[1-9][0-9]|1[0-9][0-9]|2[0-4][0-9]|25[0-5])){3}$/", $ip);
    }

    /**
     * Check if given email address is well formated
     * 
     * @param string $address   The e-mail address
     * @return boolean          Return true if the given e-mail address is correctly formated
     */
    public static function checkEmail($address)
    {
        return preg_match("/^([a-z0-9\+_\-]+)(\.[a-z0-9\+_\-]+)*@([a-z0-9\-]+\.)+[a-z]{2,6}$/ix", $address);
    }

    /**
     * Strips accents from string
     * 
     * @param string $string    The input string
     * @return string           Return the string without accents
     */
    public static function stripAccents($string)
    {
        if(!mb_detect_encoding($string, 'UTF-8', true)) trigger_error ('String is not valid UTF-8');        
        include(FW_DIR.DS.'lib'.DS.'data'.DS.'accents.php');        
        return preg_replace(array_keys($accents), array_values($accents), $string);        
    }

    /**
     * Converts a string into an url compliant string
     * 
     * @param string $string            The string to convert
     * @param string $separator         [optional] The separator to use in place of spaces or unrecognized chars, default is "-"
     * @param boolean $lowercase        [optional] Return string in lowercase if true, default is true
     * @param boolean $convertSlashes   [optional] Convert slashes to separator char if true, default is true
     * @return string                   Return the string formated to use as an url
     */
    public static function toUrl($string, $separator = '-', $lowercase = true, $convertSlashes = true)
    {
        $string = self::stripAccents($string);

        $string = strip_tags($string);

        $regexp = $convertSlashes ? '/([^a-zA-Z0-9_]+)/i' : '/([^a-zA-Z0-9_\/]+)/i';

        $string = preg_replace($regexp, $separator, $string);

        if ($lowercase) $string = strtolower($string);

        // remove separator in beginning or ending of string
        $string = preg_replace(array('/^' . $separator . '+/i', '/\\' . $separator . '+$/i'), '', $string);

        return $string;
    }

    /**
     * Truncate a string at a precise length
     *
     * @param string $string    The input string
     * @param integer $length   Length of the part of string to return
     * @param string $pad       [optional] String to add at the end of the result, default is "&hellip;"
     * @return string           The extracted part of the string
     */
    public static function truncateAtLength($string, $length, $pad = '&hellip;')
    {
        if (mb_strlen($string, '8bit') <= $length) return $string;
        return mb_substr($string, 0, $length, '8bit') . $pad;
    }

    /**
     * Truncate a string at word breaks
     *
     * @param string $string    The input string
     * @param integer $length   Length of the part of string to return
     * @param string $pad       [optional] String to add at the end of the result, default is "&hellip;"
     * @return string           The extracted part of the string   
     */
    public static function truncateAtWord($string, $length, $pad = '&hellip;')
    {       
        if (mb_strlen($string, '8bit') <= $length) return $string;

        if (false !== ($breakpoint = mb_strpos($string, ' ', $length, '8bit')))
        {
            if ($breakpoint < mb_strlen($string, '8bit') - 1)
            {
                while(!preg_match('/[a-zA-Z1-9]/', $string[$breakpoint])) $breakpoint--;
                
                return mb_substr($string, 0, $breakpoint, '8bit') . $pad;
            }
        }
        return $string;
    }

    /**
     * Truncate a string at sentence breaks
     *
     * @param string $string    The input string
     * @param integer $length   Length of the part of string to return
     * @param string $pad       [optional] String to add at the end of the result, default is "."
     * @return string           The extracted part of the string
     */
    public static function truncateAtSentence($string, $length, $pad = '.')
    {        
        if (mb_strlen($string, '8bit') <= $length) return $string;

        if (false !== ($breakpoint = mb_strpos($string, '.', $length, '8bit')))
        {
            if ($breakpoint < mb_strlen($string, '8bit') - 1)
            {
                return mb_substr($string, 0, $breakpoint, '8bit') . $pad;
            }
        }
        return $string;
    }

    /**
     * Escape MS Word special chars when copy-paste from MS Word
     *
     * @param string $text      The input string
     * @return string           The string without MS Word chars
     */
    public static function escapeWordChars($text)
    {
        $trans_tbl[chr(145)] = '&#8216;';
        $trans_tbl[chr(146)] = '&#8217;';
        $trans_tbl[chr(147)] = '&#8220;';
        $trans_tbl[chr(148)] = '&#8221;';
        $trans_tbl[chr(142)] = '&eacute;';
        $trans_tbl[chr(150)] = '&#8211;';
        $trans_tbl[chr(151)] = '&#8212;';
        return strtr($string, $trans_tbl);
    }

    /**
     * Generate a human readable random string
     * 
     * @param integer $length           [optionnal] Length of the generated string (default = 8)
     * @param boolean $onlyLowerCase    [optionnal] Get string only in lowercase (defaut = true)
     * @return type 
     */
    function hrRandom($length = 8, $onlyLowerCase = true)
    {      
        // variables initialization
        $p = 0; $result = '';
        
        // characters to build the random string with
        $chars = 'BCDFGHJKLMNPRSTVWXZbcdfghjklmnprstvwxzaeiouAEIOU';

        // building the random result
        while($p++ < $length) $result .= ($p%2) ? $chars[mt_rand(38, 47)] : $chars[mt_rand(0, 37)];

        // return in lower or uppercase
        return $onlyLowerCase ? strtolower($result) : $result;
    }       
    
    /**
     * Generate a random string (for password per example)
     * 
     * @param array $options            [optional] Array of options for generation, overrides the defaut options<br />
     *                                  - length : length of the generated string (default = 8)
     *                                  - alpha : use alphabetical characters (default = true)
     *                                  - numbers : use numbers (default = true)
     *                                  - lowercase : use lowercase characters (default = true)
     *                                  - uppercase : use uppercase characters (default = true)
     *                                  - special : use special characters (default = false)
     *                                  - repetition : check for characters repetitions like aa, ee, etc... (default is false)
     *                                  
     * @return string                   The random string with the given length
     */    
    public static function random(array $options = array())
    {      
        // variables initialization
        $chars = ''; $p = 0; $result = '';
        
        // default options
        $default = array('length'       => 8, 
                         'alpha'        => true, 
                         'numbers'      => true, 
                         'lowercase'    => true, 
                         'uppercase'    => true, 
                         'special'      => false,
                         'repetition'   => false);

        // override with the given options
        $options = array_merge($default, $options);

        
        // get all chars
        if ($options['numbers'])                         $chars .= '0123456789';
        if ($options['alpha'] && $options['lowercase'])  $chars .= 'abcdefghijklmnopqrstuvwxyz';
        if ($options['alpha'] && $options['uppercase'])  $chars .= 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        if ($options['special'])                         $chars .= '@_-&%*$#';

        // additionnal randomness
        $chars = str_shuffle($chars);

        // get the random string
        $l = strlen($chars) - 1;
        while($p++ < $options['length']) $result .= $chars[mt_rand(0, $l)];

        // check for chars repetitions in the same string
        if(!$options['repetition'] && preg_match('/([a-zA-Z0-9]{1,1})(?=\1+)/', $result)) return self::random($options);

        return $result;
    }      
    
    /**
     * Test the given password strength. An acceptable password have more than 50%
     * 
     * @param string $pwd       The password to test
     * @return integer          Password strength percentage
     */
    public static function passwordStrength($pwd)
    {
        $score = 0;
        
        // Tests on length (max score = 22)
        $len = mb_strlen($pwd);
        if($len >= 4) $score += 2;        
        if($len >= 6) $score += 4;        
        if($len >= 8) $score += 8;
        if($len >= 15) $score += 8;
                
        // Tests on chars (max score = 5)
        if(preg_match('/[a-z]/', $pwd)) $score += 2;
        if(preg_match('/[A-Z]/', $pwd)) $score += 3;        
        
        // Tests on numbers (max score = 5)
        if(preg_match('/[0-9]/', $pwd)) $score += 2;
        if(preg_match('/\d.*\d/', $pwd)) $score += 3;
        
        // Tests on non alpha chars (max score = 10)
        if(preg_match('/\W/', $pwd)) $score += 4;                
        if(preg_match('/\W.*\W/', $pwd)) $score += 6;                
        
        // Tests on repetitions (max score = 10)
        if(!preg_match('/([a-zA-Z0-9\W]{1,1})(?=\1+)/', $pwd)) $score += 5;
        if(!preg_match('/([a-z]{1,1})(?=\1+)/i', $pwd)) $score += 5;
        
        // Tests on combos (max score = 20)
        if(preg_match('/(?=.*[a-z])(?=.*[A-Z])/', $pwd)) $score += 5;
        if(preg_match('/(?=.*[a-zA-Z])(?=.*[0-9])/', $pwd)) $score += 5;
        if(preg_match('/(?=.*[a-zA-Z0-9])(?=.*[\W])/', $pwd)) $score += 10;
                               
        return round(($score/72)*100);
    }
    
    /**
     * Camelize (or Pascalize) a string
     * 
     * @example camelize('my_database_field', true) => MyDatabaseField
     * 
     * @param string $string    The string to camelize
     * @param boolea $lcfirst   [optional] First char must be lowercase ? Default is false
     * @return string           Camelized string
     */
    public static function camelize($string, $lcfirst = false)
    {
        if ($lcfirst) return lcfirst(preg_replace('/_(.?)/e', "strtoupper('$1')", $string));
        return ucfirst(preg_replace('/(?:^|_)(.?)/e', "strtoupper('$1')", $string));
    }
    
    
    /**
     * Convert a camel cased or pascal cased string to a snake cased string (with _ for separator)
     * 
     * @param string $string    The string to snake case
     * @return string           Snake cased string
     */
    public static function snakeCase($string)
    {
        return ltrim(strtolower(preg_replace('/([A-Z])/', '_$1', $string)), '_');
    }    

    /**
     * Advanced ucwords, add uppercase to the first character of each word.
     * 
     * @param string $string    The input string
     * @return string           The string with upper cased first char of words
     */
    public static function ucwords($string)
    {
        return mb_convert_case($string, MB_CASE_TITLE, 'UTF-8');
    }

    /**
     * Advanced strpos, find the first occurence of an needle string or array in haystack
     * 
     * @param string $haystack  The input string
     * @param mixed $needle     Needle to search (string or array).<br />Search will stop on first founded string/char
     * @return integer|boolean  Return position in given string or array, else return false if needle is not found
     */
    public static function strpos($haystack, $needle)
    {
        if (is_string($needle)) $needle = array($needle);
              
        foreach ($needle as $what)
        {
            if (($pos = mb_strpos($haystack, $what, 0, 'UTF-8')) !== false) return $pos;
        }
        
        return false;
    }

    /**
     * Return image src content from img tags in a text
     * 
     * @param string $content   The input string
     * @return array            Array of image src values
     */
    public static function getImgSrc($content)
    {
        preg_match_all('/< *img[^>]*src *= *["\']?([^"\']*)/i', $content, $patterns);

        return $patterns[1];
    }    
}