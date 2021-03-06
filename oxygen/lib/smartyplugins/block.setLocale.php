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
 * Set the current template original locale to use with {t}
 * 
 * @param array $params     An array of parameters to give to i18n::translate()
 * @param string $content   The template or template portion content
 * @param Smarty $smarty    The current Smarty instance
 * @param boolean $repeat   Are we in the end of {setLocale} tag
 * @return string           The template or template portion content without modification
 */
function smarty_block_setLocale($params, $content, &$smarty, &$repeat)
{
    if($repeat == true)
    {
        if(isset($params['locale']) && preg_match('/^([a-z]{2,3})[_-]([A-Z]{2})$/', $params['locale'])) 
        {
            $smarty->assign('TPL_LOCALE', $params['locale']);
        }
        else
        {
            throw new SmartyException('Locale is not set or not correctly formatted');
        }
    }
    else
    {
        $smarty->assign('TPL_LOCALE', i18n::getDefaultLocale());
        return $content;
    }
}