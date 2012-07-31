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


if(!function_exists('get_called_class'))
{
    /**
     * Retro support of get_called_class for PHP < 5.3.0
     * 
     * @see http://www.php.net/manual/en/function.get-called-class.php
     * 
     * @return string   The called class name
     */    
    function get_called_class()
    {    
        $bt = debug_backtrace();

        foreach($bt as $k => $v)
        {
            // if file is defined
            if(empty($v['file'])) continue;

            // get script content, line number and line content
            $scriptContent = file($v['file']);
            $line = $v['line'] - 1;
            $lineContent = trim($scriptContent[$line]);

            // special case.
            if($v['function'] === 'call_user_func' || $v['function'] === 'call_user_func_array') return $v['args'][0][0];

            if(preg_match('/([a-zA-Z0-9\_]+)::'.$v['function'].'/', $lineContent, $matches) && isset($matches[1]))
            {
                switch ($matches[1])
                {
                    case 'self':
                    case 'parent':
                        for ($i=$line; $i >= 0; $i--)
                        {
                            if(preg_match('/class[\s]+([a-zA-Z0-9\_]+)+?/si', trim($scriptContent[$i]), $matches)) return $matches[1];
                        }
                    break;                           

                    default:
                        return $matches[1];
                    break;
                }                      
            }                                                               
        }    
    }
}

if(!function_exists('lcfirst'))
{
    /**
     * Retro support of lcfirst for PHP < 5.3.0
     * 
     * @see http://www.php.net/manual/en/function.lcfirst.php
     * 
     * @param string $str   The input string
     * @return string       String with first char in lowercase     
     */    
    function lcfirst($str)
    {
        $str[0] = strtolower($str[0]);
        return (string) $str;
    }
}

if(!function_exists('mb_lcfirst'))
{
    /**
     * ucfirst for unicode strings
     * 
     * @param string $string
     * @return string 
     */
    function mb_lcfirst($string, $encoding = "UTF-8")
    {
        return mb_strtolower(mb_substr($string, 0, 1, $encoding), $encoding) . mb_substr($string, 1, mb_strlen($string), $encoding);
    }
}

if(!function_exists('mb_ucfirst'))
{
    /**
     * ucfirst for unicode strings
     * 
     * @param string $string
     * @return string 
     */
    function mb_ucfirst($string, $encoding = "UTF-8")
    {
        return mb_strtoupper(mb_substr($string, 0, 1, $encoding), $encoding) . mb_substr($string, 1, mb_strlen($string), $encoding);
    }
}

if(!function_exists('ucfirst_last'))
{
    /**
     * Uppercase first character of the last name in a class name
     *
     * @example m_default_index -> m_default_Index
     * 
     * @param string $class_name    Input class name string
     * @param string separator      [optional] Separator to use. Default is '_'
     * @return string               String with the first char of the last section word in uppercase
     */    
    function ucfirst_last($class_name, $separator = '_')
    {
        $args = explode($separator, $class_name);

        if(!empty($args))
        {
            $args[count($args)-1] = ucfirst($args[count($args)-1]);
            return join($separator, $args);
        }
        return ucfirst($class_name);
    }
}

if(!function_exists('to_object'))
{
    /**
     * Converts an associative array to a stdClass object recursively
     *
     * @param array $array  The array to convert
     * @return stdClass     A stdClass object
     */    
    function to_object(array $array)
    {
        foreach($array as $key => $value)
        {
            if(is_array($value)) $array[$key] = to_object($value);
        }

        return (object)$array;
    }
}

if(!function_exists('to_array'))
{
    /**
     * Converts an object to an array recursively
     *
     * @param object $obj   The object to convert
     * @return array        An associative array
     */
    function to_array($obj)
    {
        foreach($obj as $key => $value)
        {
            if(is_object($value)) $obj[$key] = @to_array($value);
        }

        return (array) $obj;
    }
}

if(!function_exists('get_module_file'))
{
    /**
     * Get the correct path to a module file (check for files in webapp directory)
     * 
     * @param string $module    The module name to check the file in
     * @param string filePath   Canonical path of the file in the module
     * @param boolean $check    [optional] Check if file exists. Default is true
     * @return string           Path to the requested file
     */
    function get_module_file($module, $filePath, $check = true)
    {   
        if($filePath[0] === DS) $filePath = substr($filePath, 1);
        
        if(is_file(WEBAPP_MODULES_DIR.DS.$module.DS.$filePath)) return WEBAPP_MODULES_DIR.DS.$module.DS.$filePath;
        
        if($check && !is_file(MODULES_DIR.DS.$module.DS.$filePath)) return false;

        return MODULES_DIR.DS.$module.DS.$filePath;
    }
}

if(!function_exists('set_header'))
{
    /**
     * Sets output header
     * 
     * @param integer $code     [optional] The http header code to send. Default is 200
     * @param string $text      [optional] The text to send to the navigator (optionnal). Default is ''
     */
    function set_header($code = 200, $text = '')
    {
		$status = array(
            200	=> 'OK',
            201	=> 'Created',
            202	=> 'Accepted',
            203	=> 'Non-Authoritative Information',
            204	=> 'No Content',
            205	=> 'Reset Content',
            206	=> 'Partial Content',

            300	=> 'Multiple Choices',
            301	=> 'Moved Permanently',
            302	=> 'Found',
            304	=> 'Not Modified',
            305	=> 'Use Proxy',
            307	=> 'Temporary Redirect',

            400	=> 'Bad Request',
            401	=> 'Unauthorized',
            403	=> 'Forbidden',
            404	=> 'Not Found',
            405	=> 'Method Not Allowed',
            406	=> 'Not Acceptable',
            407	=> 'Proxy Authentication Required',
            408	=> 'Request Timeout',
            409	=> 'Conflict',
            410	=> 'Gone',
            411	=> 'Length Required',
            412	=> 'Precondition Failed',
            413	=> 'Request Entity Too Large',
            414	=> 'Request-URI Too Long',
            415	=> 'Unsupported Media Type',
            416	=> 'Requested Range Not Satisfiable',
            417	=> 'Expectation Failed',

            500	=> 'Internal Server Error',
            501	=> 'Not Implemented',
            502	=> 'Bad Gateway',
            503	=> 'Service Unavailable',
            504	=> 'Gateway Timeout',
            505	=> 'HTTP Version Not Supported'
        );

		if ($code === '' || !is_numeric($code)) trigger_error('Status codes must be numeric', E_USER_ERROR);

        if (isset($status[$code]) && $text == '') $text = $status[$code];

		if ($text === '') trigger_error('No status text available.  Please check your status code number or supply your own message text.', E_USER_ERROR);

		$server_protocol = (isset($_SERVER['SERVER_PROTOCOL'])) ? $_SERVER['SERVER_PROTOCOL'] : false;

		if (substr(PHP_SAPI, 0, 3) === 'cgi')
		{
			header("Status: {$code} {$text}", true);
		}
		elseif ($server_protocol === 'HTTP/1.1' OR $server_protocol === 'HTTP/1.0')
		{
			header($server_protocol." {$code} {$text}", true, $code);
		}
		else
		{
			header("HTTP/1.1 {$code} {$text}", true, $code);
		}
	}
}

if (!function_exists('redirect'))
{
    /**
     * This function will redirect to the given url by using the header function.
     *
     * @param string $uri       [optional] The url where to redirect, you can use canonical urls (will take current host automaticaly). Default is home
     * @param string $method    [optional] The method to use, can be "location", "refresh" or "javascript"
     * @param integer $code     [optional] The http header code to use. Default is 302 (Moved Temporarily)
     */
	function redirect($uri = '', $method = 'location', $code = 302)
	{       
		if(!preg_match('#^https?://#i', $uri))
		{      
            if(substr($uri, 0, 1) != '/') $uri = '/'.$uri;
			$uri = Uri::getInstance()->getHost(true).$uri;
		}

        if($method === 'refresh')
        {
            header("Refresh:0;url=".$uri);
        }
        else if($method === 'javascript')
        {
            while (ob_get_level()) { ob_end_clean(); }
            echo '<script language="javascript">location.href="'.$uri.'";</script>';            
        }
        else
        {
            header("Location:".$uri, true, $code);            
        }		
		exit;
	}
}

if (!function_exists('refresh'))
{
    /**
     * This function will refresh the current page. Useful for forms but beware on loops.
     */
	function refresh()
	{    
        $url = Uri::getInstance()->getHost(true).$_SERVER['REQUEST_URI'];
        header("Location:".$url, true, $code);
		exit;
	}
}

if(!function_exists('first'))
{
    /**
     * Alias of function reset(). Returns the first element of an array
     * 
     * @param array $array      Input array
     * @return mixed            First element of the given array
     */
	function first(array $array)
	{
		return reset($array);
	}
}

if(!function_exists('last'))
{
    /**
     * Alias of function end(). Returns the last element of an array
     * 
     * @param array $array      Input array
     * @return mixed            Last element of the given array
     */
	function last(array $array)
	{
		return end($array);
	}
}

if(!function_exists('get_module_name'))
{
    /**
     * Return module name from the given class name
     * 
     * @param string $className
     * @return string 
     */
    function get_module_name($className)
    {
        preg_match('/^m_(.*?)_/', $className, $matches);
        if(!isset($matches[1])) return null;
        return $matches[1];        
    }
}