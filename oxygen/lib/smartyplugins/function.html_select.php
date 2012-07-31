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
 * Build a select tag with options
 * 
 * Params :
 *  - name           : name of the select tag
 *  - options        : associative array of options to build (can be multi-level for optgroups)
 *  - selected       : value of the selected option
 *  - label_as_value : if true, use the label as value
 *  - empty_option   : if true, start the options with an empty option
 * 
 * @param array $params
 * @param Smarty $smarty
 * @throws Exception 
 */
function smarty_function_html_select($params, &$smarty)
{
    require_once(SMARTY_PLUGINS_DIR . 'shared.escape_special_chars.php');
    
    // Check for required params
    $diff = array_diff(array('name', 'options'), array_keys($params));
    if(!empty($diff)) throw new Exception('{html_select} Required params is not set : '.$r);
    
    // Check if options is an array
    if(!is_array($params['options'])) throw new Exception('{html_select} options is not a valid array');
    
    // Get params and/or set default values
    if(isset($params['selected']))
    {
        $params['selected']       = smarty_function_escape_special_chars($params['selected']);
    }
    else
    {
        $params['selected']       = isset($params['default']) ? smarty_function_escape_special_chars($params['default']) : null;        
    }
    
    // Use label for value attribute
    $params['label_as_value'] = isset($params['label_as_value']) && $params['label_as_value'] == 1;    
    
    // Start new HTML
    $html = new XMLWriter();    
    $html->openMemory();
    
    $html->startElement('select');
    $html->writeAttribute('name', smarty_function_escape_special_chars($params['name']));
    
    if(isset($params['class'])) $html->writeAttribute('class', smarty_function_escape_special_chars($params['class']));
    if(isset($params['id'])) $html->writeAttribute('id', smarty_function_escape_special_chars($params['id']));
    
    if(isset($params['empty_option']) && $params['empty_option'] == 1) $html->writeElement('option', '---');
    
    $html = smarty_parse_html_options($params['options'], $params, $html);
    
    $html->endElement();
    
    echo html_entity_decode($html->outputMemory(), ENT_COMPAT, 'utf-8');   
}

function smarty_parse_html_options($options, $params, $html)
{
    /* @var $html XMLWriter */
   foreach($options as $k => $v)
   {
       if(is_array($v))
       {
           $html->startElement('optgroup');
           $html->writeAttribute('label', smarty_function_escape_special_chars($k));
           $html = smarty_parse_html_options($v, $params, $html);
           $html->endElement();
       }
       else
       {
           $html->startElement('option');
           
           if($params['label_as_value'])
           {
               $html->writeAttribute ('value', smarty_function_escape_special_chars($v));
               if($params['selected'] == $v) $html->writeAttribute ('selected', 'selected');
           }
           else
           {
               $html->writeAttribute ('value', smarty_function_escape_special_chars($k));
               if($params['selected'] == $k) $html->writeAttribute ('selected', 'selected');
           }
           
           $html->writeRaw($v);
           $html->endElement();              
       }
   }
   
   return $html;
}

?>