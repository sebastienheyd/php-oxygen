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
        $a = "ÀÁÂÃÄÅàáâãäåÒÓÔÕÖØòóôõöøÈÉÊËèéêëÇçÌÍÎÏìíîïÙÚÛÜùúûüÿÑñ";
        $b = "AAAAAAaaaaaaOOOOOOooooooEEEEeeeeCcIIIIiiiiUUUUuuuuyNn";

        return strtr(utf8_decode(trim($string)), utf8_decode($a), $b);
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

        $regexp = $convertSlashes ? '/([^.a-zA-Z0-9_]+)/i' : '/([^.a-zA-Z0-9_\/]+)/i';

        $string = preg_replace($regexp, $separator, $string);

        if ($lowercase) $string = strtolower($string);

        // remove separator in beginning or ending of string
        $string = preg_replace(array('/^' . $separator . '+/i', '/\\' . $separator . '+$/i'), '', $string);

        return $string;
    }

    /**
     * UTF-8 compliant substr
     *
     * @param string $str       The input string
     * @param integer $from     Offset where to start
     * @param integer $len      Length of the part of string to return
     * @return string           The extracted part of the string
     */
    public static function substru($str, $from, $len)
    {
        return preg_replace('#^(?:[\x00-\x7F]|[\xC0-\xFF][\x80-\xBF]+){0,' . $from . '}' . '((?:[\x00-\x7F]|[\xC0-\xFF][\x80-\xBF]+){0,' . $len . '}).*#s', '$1', $str);
    }

    /**
     * Truncate a string at a precise length
     *
     * @param string $string    The input string
     * @param integer $length   Length of the part of string to return
     * @param string $pad       [optional] String to add at the end of the result, default is "..."
     * @return string           The extracted part of the string
     */
    public static function truncateAtLength($string, $length, $pad = '...')
    {
        if (strlen($string) <= $length) return $string;
        return self::substru($string, 0, $length) . $pad;
    }

    /**
     * Truncate a string at word breaks
     *
     * @param string $string    The input string
     * @param integer $length   Length of the part of string to return
     * @param string $pad       [optional] String to add at the end of the result, default is "..."
     * @return string           The extracted part of the string   
     */
    public static function truncateAtWord($string, $length, $pad = '...')
    {
        if (strlen($string) <= $length) return $string;

        if (false !== ($breakpoint = strpos(utf8_decode($string), ' ', $length)))
        {
            if ($breakpoint < strlen(utf8_decode($string)) - 1)
            {
                return self::substru($string, 0, $breakpoint) . $pad;
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
        if (strlen($string) <= $length) return $string;

        if (false !== ($breakpoint = strpos(utf8_decode($string), '.', $length)))
        {
            if ($breakpoint < strlen(utf8_decode($string)) - 1)
            {
                return self::substru($string, 0, $breakpoint) . $pad;
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
     * Generate a random string (for password per example)
     * 
     * @param integer $length           [optional] Length of the randow string to generate, default is 8
     * @param boolean $numbers          [optional] Can use numbers if true, default is true
     * @param boolean $uppercase        [optional] Can use uppercase chars if true, default is true
     * @param boolean $specialChars     [optional] Can use special chars if true, default is false
     * @return string                   The random string with the given length
     */
    public static function random($length = 8, $numbers = true, $uppercase = true, $specialChars = false)
    {
        $chars = 'abcdefghijkmnopqrstuvwyxz';

        if ($numbers)       $chars .= '123456789';
        if ($uppercase)     $chars .= 'ABCDEFGHJKLMNPQRSTUVWXYZ';
        if ($specialChars)  $chars .= '@_-&%*$#';

        // additionnal randomness
        $chars = str_shuffle($chars);
        
        $l = strlen($chars) - 1;

        for ($p = 0; $p < $length; $p++)
        {
            $result .= $chars[mt_rand(0, $l)];
        }

        return $result;
    }

    /**
     * Camelize (or Pascalize) a string
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
     * Advanced ucwords
     * 
     * @param string $string    The input string
     * @return string           The string with upper cased first char of words
     */
    public static function ucwords($string)
    {
        return ucfirst(preg_replace('/([^a-zA-Z0-9])([a-z]{1})?/e', "'$1'.strtoupper('$2')", strtolower($string)));
    }

    /**
     * Advanced strpos
     * 
     * @param string $haystack  The input string
     * @param mixed $needle     Needle to search (string or array).<br />Search will stop on first founded string/char
     * @return integer|boolean  Return position in given string or array, else return false if needle is not found
     */
    public static function strpos($haystack, $needle)
    {
        if (!is_array($needle)) $needle = array($needle);
        
        foreach ($needle as $what)
        {
            if (($pos = strpos($haystack, $what)) !== false) return $pos;
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