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

class I18n
{   
    /**
     * Translate the given string to the current i18n locale language.
     * 
     * @param string $file          The XLIFF file to parse
     * @param string $string        The string to translate
     * @param array $args           [optional] Associative array of elements to replace in the given string.<br />Exemple : translate('My name is %name%', array('name' => 'Jim'))
     * @param type $srcLang         [optional] ISO 639-1 code of the source language. Default is 'en'
     * @param string $origin        [optional] The name of the original file where is located the given string. Default is 'default'
     * @param boolean $addToFile    [optional] If not found with the current language, add to xliff file ?
     * @return string               The translated string if found else the source string, default is true
     */
    public static function translate($file, $string, $args = array(), $srcLang = 'en', $origin = 'default', $addToFile = true)
    {
        // if current lang is different from the given string lang
        if($srcLang != self::getLang()) return f_i18n_Xliff::getInstance($file)->translate($string, $args, $srcLang, $origin, $addToFile);                           
        
        // replace args in string
        if(!empty($args))
        {
            foreach($args as $k => $v)
            {
                $string = str_replace("%$k%", $v, $string);
            }
        }
        
        // return translated string
        return $string;
    }
    
    /**
     * Set the locale code to use for the current session composed by :<br />
     * ISO 639-1 code and ISO 3166-1 code separated by _ or - for the full locale (ex : fr_FR, en_US)<br />
     * ISO 639-1 code in lowercase just for the lang (ex : fr, en, es)<br />
     * ISO 3166-1 code in uppercase just for the region (ex : FR, DE, US)
     * 
     * @example setLocale('fr_FR');
     * @param string $locale            The locale code, lang code or region code to set
     * @return void
     */
    public static function setLocale($locale)
    {        
        // set full locale like fr_FR
        if(preg_match('/^([a-z]{2,3})[_-]([A-Z]{2})$/i', $locale, $m))
        {
            Session::set('locale', $m[0]);
            Session::set('lang', $m[1]);
            Session::set('region', $m[2]);
        }
        else if(preg_match('/^([a-z]{2,3})$/i', $locale, $m)) // only set lang
        {
            Session::set('lang', $m[0]);
        }
        else if(preg_match('/^([A-Z]{2,3})$/i', $locale, $m)) // only set region
        {
            Session::set('region', $m[0]);       
        }
        else
        {
            // locale is not well formatted, returns an error
            trigger_error('Locale code is not valid : '.$locale, E_USER_ERROR);                          
        }       
    }
    
    /**
     * Get current session locale composed by ISO 639-1 code and ISO 3166-1 code separated by _ or -
     * 
     * @example getLocale('fr_FR');
     * @param string $default   [optional] Default value to get if no locale is set. Default is 'en_US'
     * @return string
     */
    public static function getLocale($default = 'en_US')
    {
        return Session::get('locale', $default);
    }
    
    /**
     * Returns current setted locale in full text
     * 
     * @example getLocaleLabel('fr') = "Français (France)"
     * 
     * @param string $lang  [optional] ISO 639-1 code of output language, set "native" for the native language. Default is null (get the current language)
     * @return string       The full text in format "Lang (Region)"
     */
    public static function getLocaleLabel($lang = null)
    {
        if($lang === null) $lang = self::getLang();
        return self::getLangLabel($lang).' ('.self::getRegionLabel($lang).')';        
    }
    
    /**
     * Get current session lang in ISO 639-1
     * 
     * @param string $default   [optional] Default lang if no lang is set, default is "en"
     * @return string
     */
    public static function getLang($default = 'en')
    {
        return Session::get('lang', strtolower($default));
    }
    
    /**
     * Returns current lang in full text
     * 
     * @param string $lang  [optional] ISO 639-1 code of output language, set "native" for the native language. Default is null (get the current language)
     * @return string       Lang in full text
     */
    public static function getLangLabel($lang = null)
    {
        if($lang === null) $lang = self::getLang();
        $xml = simplexml_load_file(FW_DIR.DS.'i18n'.DS.'xml'.DS.'iso-639.xml');        
        $xlang = $xml->xpath('/codes/language[@iso-639-1="'.self::getLang().'"]/label[@lang="'.strtolower($lang).'"]');  
        if(empty($xlang)) return $lang != 'en' ? self::getLangLabel('en') : '/';   
        return (string) $xlang[0];
    }
    
    /**
     * Get current region code ISO3166-1 
     * 
     * @param string $default   [optional] Default ISO3166-1 code if no region is setted. Default is 'US'
     * @return string 
     */
    public static function getRegion($default = 'US')
    {
        return Session::get('region', strtoupper($default));
    }
    
    
    /**
     * Returns region in full text
     * 
     * @param string $lang  [optional] ISO 639-1 code of output language, set "native" for the native language. Default is null (get the current language)
     * @return string       Region in full text      
     */
    public static function getRegionLabel($lang = null)
    {
        if($lang === null) $lang = self::getLang();
        $xml = simplexml_load_file(FW_DIR.DS.'i18n'.DS.'xml'.DS.'iso-3166.xml');
        $region = $xml->xpath('/regions/region[@code="'.self::getRegion().'"]/label[@lang="'.strtolower($lang).'"]');
        if(empty($region)) return $lang != 'en' ? self::getRegionLabel('en') : '/'; 
        return (string) $region[0];
    }
    /**
     * Return the browser current language
     * 
     * @param string $default   [optional] Default language ISO 639-1 code if browser language is not found
     * @return string           Return browser's ISO 639-1 code
     */
    public static function getBrowserLanguage($default = 'en')
    {
        if(!isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])  || $_SERVER['HTTP_ACCEPT_LANGUAGE'] == '') return strtolower($default);
       
        $q = 0;
        foreach(explode(',', $_SERVER['HTTP_ACCEPT_LANGUAGE']) as $v)
        {
            $nq = 1.0;
            
            if(preg_match("/(.*);q=([0-1]{0,1}\.\d{0,4})/i",$v,$m))
            {
                $v = $m[1];
                $nq = (float) $m[2];
            }
            
            if($nq > $q)
            {
                $q = $nq;
                $res = $v;
            }
        }   
        
        return preg_replace('/-(.*)$/i', '', $res);
    }
}
