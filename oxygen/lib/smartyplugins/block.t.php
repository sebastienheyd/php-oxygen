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
        $srcLang = isset($smarty->tpl_vars['TPL_LANG']) ? $smarty->tpl_vars['TPL_LANG']->value : 'en';        

        $path = explode(DS, str_replace(APP_DIR.DS, '', $smarty->template_resource));
        
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

        if($isModule !== false)
        {
            $file = get_module_file($module, '/i18n/templates.'.I18n::getLocale().'.xml');
            
            if($file !== false)
            {
                $str = I18n::t($file, $content, $params, $srcLang, end($path));
                if($str != $content) return $str;
            }    
            
            $file = get_module_file($module, '/i18n/templates.'.I18n::getLang().'.xml', false);
            return I18n::t($file, $content, $params, $srcLang, end($path));        
        } 
        
        return $content;
    }
}