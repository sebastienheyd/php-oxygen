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
 * This method will get a module/action or module template result
 * 
 * @param array $params     
 * @param Smarty $smarty 
 */
function smarty_function_widget($params, &$smarty)
{
    if(!isset($params['module']) || 
       (!isset($params['action']) && !isset($params['template'])) ||
       (isset($params['action']) && isset($params['template'])))
	{
        throw new SmartyException ('{widget} : module and action (or template) parameters must be defined');
	}
    
    $args = array();
    
    if(count($params) > 2)
    {
        foreach($params as $k => $v)
        {
            if($k !== 'module' && $k !== 'action' && $k !== 'template') $args[$k] = $v;
        }
    }
    
    if(isset($params['action']))
    {
        Controller::getInstance()->setModule($params['module'])->setAction($params['action'])->setArgs(array($args))->dispatch();        
    }
    else
    {
        if($file = get_module_file($params['module'], 'template/'.$params['template'], true))
        {
            $tpl = Template::getInstance($file);
                                    
            foreach($smarty->tpl_vars as $k => $v)
            {
                if($k !== 'SCRIPT_NAME' && $k !== 'smarty' && !isset($args[$k])) $args[$k] = $v->value;
            }
            
            if(empty($args)) return $tpl->get();

            foreach($args as $name => $value) $tpl->assign($name, $value);
                           
            return $tpl->get();        
        }
    }
}