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
 * This method will get a module/action result
 * 
 * @param array $params     
 * @param Smarty $smarty 
 */
function smarty_function_widget($params, &$smarty)
{
    if(!isset($params['module']) || !isset($params['action']))
	{
        throw new SmartyException ('{widget} : module and action parameters must be defined');
	}
    
    $args = array();
    
    if(count($params) > 2)
    {
        foreach($params as $k => $v)
        {
            if($k != 'module' && $k != 'action')
            {
                $args[$k] = $v;
            }
        }
    }   

    Controller::getInstance()->setModule($params['module'])->setAction($params['action'])->setArgs(array($args))->dispatch();
}
