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

class I18n
{   
    private static $_iso_3166;
    private static $_defaultLang;
    private static $_defaultRegion;
    
    /**
     * Translate the given string to the current i18n locale language.
     * 
     * @param string $file          The XLIFF file to parse
     * @param string $string        The string to translate
     * @param array $args           [optional] Associative array of elements to replace in the given string.<br />Exemple : translate('My name is %name%', array('name' => 'Jim'))
     * @param type $srcLang         [optional] ISO 639-1 code of the source language. Default is null (will take the default lang)
     * @param string $origin        [optional] The name of the original file where is located the given string. Default is 'default'
     * @param boolean $addToFile    [optional] If not found with the current language, add to xliff file ? Default is true
     * @return string               The translated string if found else the source string
     */
    public static function t($file, $string, $args = array(), $srcLang = null, $origin = 'default', $addToFile = true)
    {
        // if current locale is equal to default locale we don't need to translate
        if(self::getDefaultLocale() === self::getLocale()) return self::replaceArgs($string, $args);
        
        // $srcLang is no set we get the application default lang
        if($srcLang === null) $srcLang = self::getDefaultLang();        
            
        // Translate string and return it
        return f_i18n_Xliff::getInstance($file)->translate($string, $args, $srcLang, $origin, $addToFile);        
    }
    
    /**
     * Replace keys in string by their values
     * 
     * @param string $string    Input string to replace keys to values
     * @param array $args       [optional] Associative array of elements to replace in the given string.<br />Exemple : translate('My name is %name%', array('name' => 'Jim'))
     * @return string
     */
    public static function replaceArgs($string, $args)
    {
        // no args = no replacement = direct return
        if(empty($args)) return $string;

        // args replacement in string
        foreach($args as $k => $v) $string = str_replace("%$k%", $v, $string);
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
        if(preg_match('/^([a-z]{2,3})[_-]([A-Z]{2})$/', $locale, $m))
        {
            Session::set('locale', $m[0]);
            Session::set('lang', $m[1]);
            Session::set('region', $m[2]);
        }
        else if(preg_match('/^([a-z]{2,3})$/', $locale, $m)) // only set lang
        {
            Session::set('lang', $m[0]);
        }
        else if(preg_match('/^([A-Z]{2,3})$/', $locale, $m)) // only set region
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
     * Set the default locale code to use for the current session. 
     * Locale code will be taken from the configuration file, else it will be "en_US"
     */
    public static function setDefaultLocale()
    {
        self::setLocale(self::getDefaultLocale());
    }
    
    /**
     * Get current session locale composed by ISO 639-1 code and ISO 3166-1 code separated by _ or -
     * 
     * @return string       Locale code composed by ISO 639-1 code and ISO 3166-1 code
     */
    public static function getLocale()
    {
        return Session::get('locale', self::getDefaultLocale());
    }
    
    /**
     * Returns the default locale setted in config, else "en_US"
     * 
     * @return string       Locale code composed by ISO 639-1 code and ISO 3166-1 code
     */
    public static function getDefaultLocale()
    {
        return Config::get('general.locale', 'en_US');
    }    
    
    /**
     * Returns current setted locale in full text
     * 
     * @example getLocaleLabel('fr') = "Français (France)"
     * 
     * @param string $outputLang    [optional] ISO 639-1 code of output language, set "native" for the native language. Default is null (get the current language)
     * @return string               The full text in format "Lang (Region)"
     */
    public static function getLocaleLabel($outputLang = null)
    {
        if($outputLang === null) $outputLang = self::getLang();
        return self::getLangLabel($outputLang).' ('.self::getRegionLabel($outputLang).')';        
    }
    
    /**
     * Get current session lang in ISO 639-1, else returns the default lang
     * 
     * @return string
     */
    public static function getLang()
    {
        return Session::get('lang', self::getDefaultLang());
    }
    
    /**
     * Returns the default lang setted in config, else "en"
     * 
     * @return string       Lang iso in ISO 639-1
     */
    public static function getDefaultLang()
    {
        if(!isset(self::$_defaultLang))
        {
            $locale = Config::get('general.locale', 'en_US');
            list($lang, $region) = preg_split('/[-_]/', $locale);
            self::$_defaultLang = strtolower($lang);            
        }
        return self::$_defaultLang;
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
     * @return string 
     */
    public static function getRegion()
    {
        return Session::get('region', self::getDefaultRegion());
    }
    
    /**
     * Returns the default region setted in config, else "US"
     * 
     * @return string       Region iso in ISO 3166-1 
     */
    public static function getDefaultRegion()
    {
        if(!isset(self::$_defaultRegion))
        {
            $locale = Config::get('general.locale', 'en_US');
            list($lang, $region) = preg_split('/[-_]/', $locale);
            self::$_defaultRegion = strtoupper($region);
        }
        return self::$_defaultRegion;
    }    
    
    /**
     * Returns region in full text
     * 
     * @param string $region    [optional] ISO 3166 code of country. Default is null (get the current country)
     * @param string $lang      [optional] ISO 639-1 code of output language. Default is null (get the current language)
     * @return string           Region in full text      
     */
    public static function getRegionLabel($region = null, $lang = null)
    {
        if($lang === null) $lang = self::getLang();
        if($region === null) $region = self::getRegion();
        
        if(self::$_iso_3166 === null)
        {
            $dir = FW_DIR.DS.'i18n'.DS.'iso-3166';        
            $file = $dir.DS.strtolower($lang).'.json';
            if(!is_file($file)) $file = $dir.DS.'en.json';
            self::$_iso_3166 = json_decode(file_get_contents($file), true);            
        }
        
        if(!isset(self::$_iso_3166[strtoupper($region)])) return $region;
        
        return self::$_iso_3166[strtoupper($region)];
    }
    
    /**
     * Return a select box with list of countries. Options values are ISO 3166 code of the country
     * 
     * @param string $lang          [optional] ISO 639-1 code of output language. Default is null (get the current language)
     * @param string $selected      [optional] ISO 3166 code of country. Default is null (get the current country)
     * @param string $name          [optional] Name attribute of the select element. Default is "country"
     * @return string               <select> element  
     */
    public static function getCountrySelectBox($lang = null, $selected = null, $name = 'country')
    {
        if($lang === null) $lang = self::getLang();
        
        if(self::$_iso_3166 === null)
        {
            $dir = FW_DIR.DS.'i18n'.DS.'iso-3166';        
            $file = $dir.DS.strtolower($lang).'.json';
            if(!is_file($file)) $file = $dir.DS.'en.json';
            self::$_iso_3166 = json_decode(file_get_contents($file), true);            
        }       
             
        $html = XML::writer(false);
        
        $html->startElement('select', array('name' => $name));
        
        $html->writeElement('option', '---'); 
        
        foreach(self::$_iso_3166 as $iso => $label)
        {            
            $attr = array();
            $attr['value'] = $iso;
            if($selected !== null && strtoupper($selected) === $iso) $attr['selected'] = 'selected';      
            
            $html->writeElement('option', $label, $attr);            
        }
        
        $html->endElement();
        return $html->outputMemory();
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
