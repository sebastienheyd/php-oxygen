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

/**
 * This method will translate or create an enter in the module xliff locale file
 * 
 * @param array $params         An array of parameters to give to i18n::translate()
 * @param string $content       The string to translate {t}...content...{/t}
 * @param Smarty $smarty        The current Smarty instance
 * @param boolean $repeat       Are we in the end of {t} tag
 * @return string               The translated string, else the original string
 */
function smarty_block_t($params, $content, &$smarty, &$repeat)
{
    if($repeat == false)
    {        
        // we get the template locale if setted by {setLocale} tag
        $srcLocale = isset($smarty->tpl_vars['TPL_LOCALE']) ? $smarty->tpl_vars['TPL_LOCALE']->value : i18n::getDefaultLocale();
        
        // if template locale is equal to current locale we don't need to translate
        if($srcLocale === i18n::getLocale()) return i18n::replaceArgs($content, $params);

        // we get the template path
        $path = explode(DS, str_replace(APP_DIR.DS, '', $smarty->template_resource));
        
        // module var has been setted in smarty
        if(isset($smarty->smarty->module))
        {
            $isModule = true;
            $module = $smarty->smarty->module;
        }
        else
        {
            $isModule = array_search('module', $path); 
            if($isModule !== false) $module = $path[$isModule+1];
        }

        // we are in a module, we can translate
        if($isModule !== false)
        {                        
            // Get locale file with full locale code (ex : fr_CA) for overloading
            if($file = get_module_file($module, '/i18n/templates.'.$srcLocale.'.xml'))
            {
                $str = I18n::t($file, $content, $params, $srcLang, end($path));
                if($str !== $string) return $str;   // There is a specific translation for full locale code, return
            }
            
            // get the lang code
            preg_match('/^([a-z]{2,3})[_-]([A-Z]{2})$/', $srcLocale, $m);
            $srcLang = $m[0];
            
            // If default lang is equal to the current lang : return
            if($srcLang == i18n::getLang()) return i18n::replaceArgs($content, $params);
            
            // Get locale file with only lang code (ex : fr) or create it
            $file = get_module_file($module, '/i18n/templates.'.I18n::getLang().'.xml', false);
            return I18n::t($file, $content, $params, $srcLang, end($path));        
        } 
        
        return i18n::replaceArgs($content, $params);
    }
}