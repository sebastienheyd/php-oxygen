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
        return preg_replace_callback('/\\\u(\w\w\w\w)/', 
                    create_function(
                            '$matches', 
                            'return html_entity_decode("&#".hexdec($matches[1]).";", ENT_COMPAT, '.$encoding.');'), 
                    $json);
    }
    
    /**
    * Indents a flat JSON string to make it more human-readable.
    *
    * @param string $json       The original JSON string to process.
    * @param string $encoding   [optional] The encoding to use Default is UTF-8
    *
    * @return string            Indented version of the original JSON string.
    */
    public static function indent($json, $encoding = 'UTF-8') 
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

            if ($char === '"' && $prevChar !== '\\') 
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