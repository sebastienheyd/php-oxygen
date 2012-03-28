<?php

function smarty_function_value($params, &$smarty)
{
    /* @var $smarty Smarty */        
    
    if(!isset($params['name'])) throw new SmartyException ('{value} : name property must be set');
    
    $var = $smarty->getTemplateVars('value');
    
    if($var !== null)
    {
        if(!isset($var->$params['name'])) return '';
    }
    
    $value = '';
    
    if(isset($var->$params['name']))
    {
        $value = $var->$params['name'];
        if(get_magic_quotes_gpc()) $value = stripslashes($value);       
    }
    
    return $value;
}
