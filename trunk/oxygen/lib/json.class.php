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

class Json
{
    /**
     * Escape unicode chars in json.
     * 
     * @param string $json      The original JSON string to process.
     * @param string $encoding  [optional] The encoding to use. Default is UTF-8
     * 
     * @return string           The escaped JSON string
     */
    
    public static function escapeUnicode($json, $encoding = 'UTF-8')
    {
        $json = preg_replace_callback(
                '/\\\\u(D[89ab][0-9a-f]{2})\\\\u(D[c-f][0-9a-f]{2})/i',
                create_function('$matches', '
                    $d = pack("H*", $matches[1].$matches[2]);
                    return mb_convert_encoding($d, "UTF-8", "UTF-16BE");')
                , $json);

        $json = preg_replace_callback('/\\\\u([0-9a-f]{4})/i',
            create_function('$matches', '
                $d = pack("H*", $matches[1]);
                return mb_convert_encoding($d, "UTF-8", "UTF-16BE");')
            , $json);
        
        $json = html_entity_decode($json, ENT_COMPAT, 'UTF-8');
        
        return $json;
    }
    
    /**
     * Encode an array into an indented JSON
     * 
    * @param array $array               Array to encode
     * @param boolean $indentArrays     [optional] Must we indent arrays []. Default is true
     * @param type $encoding            [optional] The encoding to use. Default is UTF-8
     * 
     * @return string                   Indented JSON string 
     */
    public static function encode($array, $indentArrays = true, $encoding = 'UTF-8')
    {
        return stripslashes(self::indent(json_encode($array), $indentArrays, $encoding));
    }
    
    /**
     * Display indented json
     * 
     * @param array $array              Array to encode
     * @param boolean $indentArrays     [optional] Must we indent arrays []. Default is true
     * @param type $encoding            [optional] The encoding to use. Default is UTF-8
     * 
     * @return string                   Indented JSON string 
     */
    public static function output($array, $indentArrays = true, $encoding = 'UTF-8')
    {
        while (ob_get_level()) { ob_end_clean(); }
        header('content-type: application/json; charset=utf-8');
        echo self::encode($array, $indentArrays, $encoding);
        exit();
    }
    
    /**
    * Indents a flat JSON string to make it more human-readable.
    *
    * @param string $json              The original JSON string to process.
    * @param boolean $indentArrays     [optional] Must we indent arrays []. Default is true
    * @param string $encoding          [optional] The encoding to use. Default is UTF-8
    *
    * @return string                   Indented version of the original JSON string.
    */
    public static function indent($json, $indentArrays = true, $encoding = 'UTF-8') 
    {
        $json = self::escapeUnicode($json, $encoding);
        $json = str_replace(array(PHP_EOL, "\t"), '', trim($json));
        
        $result      = '';
        $pos         = 0;
        $strLen      = strlen($json);
        $indentStr   = "\t";
        $prevChar    = '';
        $outOfQuotes = true;

        for ($i = 0; $i <= $strLen; $i++) 
        {
            $char = $json[$i];
            
            if ($char === '"' && $prevChar !== '\\' || (!$indentArrays && ($char === '[' || $char === ']')))
            {
                $outOfQuotes = !$outOfQuotes;
            } 
            else if(($char === '}' || $char === ']') && $outOfQuotes) 
            {
                $result .= PHP_EOL.str_pad('', --$pos, $indentStr, STR_PAD_LEFT);
            }

            $result .= $char;

            if (($char === ',' || $char === '{' || $char === '[') && $outOfQuotes) 
            {
                $result .= PHP_EOL;
                if ($char === '{' || $char === '[') $pos++;
                $result .= str_pad('', $pos, $indentStr, STR_PAD_LEFT);
            }

            $prevChar = $char;
        }

        return $result;
    }
}