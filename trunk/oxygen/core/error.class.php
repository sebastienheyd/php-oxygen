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

class Error
{   
    private $_levels = array(
                    E_ERROR				=>	'Error',
                    E_WARNING			=>	'Warning',
                    E_PARSE				=>	'Parsing Error',
                    E_NOTICE			=>	'Notice',
                    E_CORE_ERROR		=>	'Core Error',
                    E_CORE_WARNING		=>	'Core Warning',
                    E_COMPILE_ERROR		=>	'Compile Error',
                    E_COMPILE_WARNING	=>	'Compile Warning',
                    E_USER_ERROR		=>	'User Error',
                    E_USER_WARNING		=>	'User Warning',
                    E_USER_NOTICE		=>	'User Notice',
                    E_STRICT			=>	'Runtime Notice'
                );

// ======================================================================== HANDLERS
    
    /**
     * Main error handler, called by init.php
     * 
     * @param integer   $errno      Type of error as integer
     * @param string    $errstr     Error message
     * @param string    $errfile    Error file
     * @param integer   $errline    Error line
     * @return void 
     */
    public function errorHandler($errno, $errstr, $errfile, $errline)
    {
        if(error_reporting() == 0) return false;                
        
        $label = isset($this->_levels[$errno]) ? $this->_levels[$errno] : $errno;
        $msg = str_replace(PROJECT_DIR.DS, '', $errstr);
        $file = str_replace(PROJECT_DIR.DS, '', $errfile);

        switch ($errno)
		{
		    case E_NOTICE:
            case E_WARNING:
            case E_USER_NOTICE:
            case E_USER_WARNING:
            case E_CORE_WARNING:
            case E_COMPILE_WARNING:
                if(!CLI_MODE)
                {
                    echo '<code><span style="font-size:14px;"><strong style="color:#900;">'.$label.'</strong> : <strong>"'.$msg.'"</strong> in <i>'.$file.'</i> (ln.'.$errline.')<br /></span></code>';                    
                }
                else
                {
                    $cli = Cli::getInstance();
                    $cli->printf('['.$label.']', 'yellow');
                    $cli->printf(' -> '.$msg.' in '.$file.' (ln.'.$errline.')'.PHP_EOL);
                }
		    break;
        
            default: 
                if(!CLI_MODE)
                {
                    $message = '"'.$msg.'" in <i>'.$file.'</i> (ln.'.$errline.')';
                    $this->_showError($label, $message, debug_backtrace());
                }
                else
                {
                    $message = '"'.$msg.'" in '.$file.' (ln.'.$errline.')';
                    $this->_showCliError($label, $message, debug_backtrace());
                }
                exit;
            break;                           
        }
    }
    
    /**
     * Main exception handler called by init.php
     * 
     * @param Exception $exception 
     * @return void
     */
    public function exceptionHandler(Exception $exception)
    {
        /* @var $exception Exception */        
        $trace = $exception->getTrace();
        
        $t = array();
        $t['args'] = isset($trace[0]['args']) ? $trace[0]['args'] : '';
        $t['line'] = $exception->getLine();
        $t['file'] = $exception->getFile();
        
        $f = file($exception->getFile());
        
        $t['function'] = '';
        $t['exception'] = trim($f[$exception->getLine()-1]);
        
        array_unshift($trace, $t);     
        
        if(CLI_MODE)
        {            
            $message = '"'.$exception->getMessage().'" in '.str_replace(PROJECT_DIR.DS, '', $exception->getFile()).' (ln.'.$exception->getLine().')';         
            $this->_showCliError(get_class($exception), $message, $trace);  
        }
        
        $message = '"'.$exception->getMessage().'" in <i>'.str_replace(PROJECT_DIR.DS, '', $exception->getFile()).'</i> (ln.'.$exception->getLine().')'; 
        $this->_showError(get_class($exception), $message, $trace);                    
    }
    
// ======================================================================== ERROR DISPLAY
    
    /**
     * Stop script and outputs an error page in CLI mode
     * 
     * @param string    $type       Type of error (the error page title)
     * @param string    $message    Error message
     * @param array     $backtrace  Array of parsed backtrace
     * @return void
     */    
    private function _showCliError($type, $message, $backtrace)
    {
        $cli = Cli::getInstance();
        
        $cli->printf('['.$type.']', 'red');
        $cli->printf(' -> '.$message.PHP_EOL);
        
        $bt = $this->_parseBacktrace($backtrace);
        
        $nb = count($bt);
        foreach($bt as $k => $line)
        {            
            $cli->printf('[debug'.$nb--.']', 'cyan');
            $cli->printf(' -> ');
            
            if(isset($line['exception']))
            {
                $cli->printf($line['exception']);
            }
            else
            {
                $cli->printf($line['function'].'('.$line['args'].')');
            }
            
            if(isset($line['line']) && isset($line['file'])) $cli->printf (' / '.$line['file'].' ln.'.$line['line']);
                
            $cli->printf(PHP_EOL);
        }
        die();
    }
    
    /**
     * Stop script and outputs an error page
     * 
     * @param string    $type       Type of error (the error page title)
     * @param string    $message    Error message
     * @param array     $backtrace  Array of parsed backtrace
     * @param string    $template   [optional] The error template to use, default is 'error.html'
     * @param integer   $header     [optional] Error code, default is 500
     * @return void
     */
    private function _showError($type, $message, $backtrace, $template = "error.html", $header = 500)
    {
        while (ob_get_level()) { ob_end_clean(); } 
        set_header($header);  

        $bt = $this->_parseBacktrace($backtrace);
        
        $tpl = Template::getInstance();
        $tpl->setTemplateDir(HOOKS_DIR.DS.'errors');
        $tpl->addTemplateDir(FW_DIR.DS.'errors');
        
        $tpl->assign('bt', $bt);
        $tpl->assign('nbStack', count($bt));        
        $tpl->assign('type', $type);
        $tpl->assign('message', $message);

        try
        {
            $tpl->setTemplate($template)->render();            
        } 
        catch (Exception $exc)
        {
            echo $exc->getMessage();
        }
        
        die();
    }
    
    /**
     * Parse backtrace to an array and add additionnal informations
     * 
     * @param   array   $bt     debug_backtrace or exception trace
     * @return  array 
     */
    private function _parseBacktrace($bt)
    {
        $result = array();    

        if(!empty($bt))
        {
            foreach($bt as $k  => $v)
            {
                if(isset($v['class']) && $v['class'] == get_class()) continue;
                
                $r = array();
                
                $r['function'] = isset($v['class']) ? $v['class'].$v['type'].$v['function'] : $v['function'];                    

                if(isset($v['exception'])) $r['exception'] = $v['exception'];
                
                if(!empty($v['args']))
                {
                    $args = array();
                    foreach($v['args'] as $arg)
                    {
                        
                        $a = $arg;
                        if(is_null($arg)) $a = 'null';
                        if(is_string($arg)) $a = "'".$arg."'";
                        if(is_object($arg)) $a = "object('".get_class($arg)."')";
                        if(is_array($arg)) $a = 'array()';
                        $args[] = $a;
                    }

                    $r['args'] = join(', ', $args);
                }
                else
                {
                    $r['args'] = '';
                }

                if(isset($v['line']) && isset($v['file']))
                {
                    $r['line'] = $v['line'];                    
                    $r['file'] = str_replace(PROJECT_DIR.DS, '', $v['file']);                
                }
                
                if(!empty($v['file']))
                {
                    $f = file($v['file']);
                    $r['code'] = isset($f[$v['line']-1]) ? trim($f[$v['line']-1]) : '';

                    $nbLines = 9;
                    $offset = $v['line'] - ceil($nbLines/2) < 0 ? 0 : $v['line'] - ceil($nbLines/2);               

                    foreach(array_slice($f, $offset, $nbLines) as $k => $line)
                    {
                        $line = str_replace(PHP_EOL, '', $line);
                        $str = highlight_string('<?php '.$line.' ?>', true);
                        $str = preg_replace('#(&lt;\?.*?)(php)?(.*?&nbsp;)#s','',$str);
                        $str = preg_replace('#(\?&gt;)#s','',$str);
                        $str = str_replace(array('<code>', '</code>'), array('',''), $str);

                        $r['fragment'][++$offset] = $str;
                    }                                    
                }
                
                $result[] = $r;                
            }            
        }

        return $result;
    }
    
// ======================================================================== PREFORMATED ERROR PAGES    
    
    /**
     * Stop script and show a 401 error page
     * 
     * @return void
     */
    public static function show401()
    {
        while (ob_get_level()) { ob_end_clean(); }
        set_header(401);

        $tpl = Template::getInstance();
        $tpl->setTemplateDir(HOOKS_DIR.DS.'errors');
        $tpl->addTemplateDir(FW_DIR.DS.'errors');        

        try
        {
            $tpl->setTemplate('401.html')->render();            
        } 
        catch (Exception $exc)
        {
            echo $exc->getMessage();
        }
        
        die();
    }    

    /**
     * Stop script and show a 404 error page
     * 
     * @return void
     */
    public static function show404()
    {
        while (ob_get_level()) { ob_end_clean(); }
        set_header(404);

        $uri = Uri::getInstance()->getUri(true);

        $tpl = Template::getInstance();
        $tpl->setTemplateDir(HOOKS_DIR.DS.'errors');
        $tpl->addTemplateDir(FW_DIR.DS.'errors');
        
        $tpl->assign('uri', $uri);

        try
        {
            $tpl->setTemplate('404.html')->render();            
        } 
        catch (Exception $exc)
        {
            echo $exc->getMessage();
        }
        
        die();
    }

    /**
     * Stop script and show a 404 error page
     * 
     * @return void
     */
    public static function showConfigurationError()
    {
        while (ob_get_level()) { ob_end_clean(); }
        set_header(404);

        $uri = Uri::getInstance()->getUri();

        $tpl = Template::getInstance();
        $tpl->setTemplateDir(HOOKS_DIR.DS.'errors');
        $tpl->addTemplateDir(FW_DIR.DS.'errors');
        
        $tpl->assign('uri', $uri);
        $tpl->assign('routeFilePath', trim(CACHE_DIR.DS.'routes.xml'));

        try
        {
            $tpl->setTemplate('configuration.html')->render();            
        } 
        catch (Exception $exc)
        {
            echo $exc->getMessage();
        }
        
        die();
    }
        
    
}