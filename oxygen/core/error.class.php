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
// For compatibility with PHP 5.3
if (version_compare(PHP_VERSION, '5.3.0', '<') === true)
{
    define('E_USER_DEPRECATED', 16384);
    define('E_DEPRECATED', 8192);
}

class Error
{

    private $_levels = array(
        E_ERROR => 'Error',
        E_WARNING => 'Warning',
        E_PARSE => 'Parsing Error',
        E_NOTICE => 'Notice',
        E_CORE_ERROR => 'Core Error',
        E_CORE_WARNING => 'Core Warning',
        E_COMPILE_ERROR => 'Compile Error',
        E_COMPILE_WARNING => 'Compile Warning',
        E_USER_ERROR => 'User Error',
        E_USER_WARNING => 'User Warning',
        E_USER_NOTICE => 'User Notice',
        E_USER_DEPRECATED => 'Deprecated Error',
        E_STRICT => 'Runtime Notice',
        E_DEPRECATED => 'Deprecated Error'
    );

    const OFF = 0;
    const DEBUG = 10;
    const STRICT = 20;

// ======================================================================== HANDLERS

    /**
     * Main error handler, called by init.php
     * 
     * @param integer   $errno      Level of the error raised
     * @param string    $errstr     Error message
     * @param string    $errfile    Filename that the error was raised in
     * @param integer   $errline    Line number the error was raised at
     * @param array     $errcontext   Array of every variable that existed in the scope the error was triggered in
     * @return void 
     */
    public function errorHandler($errno, $errstr, $errfile, $errline, $errcontext)
    {
        // Special case for @ error-control operator
        if (error_reporting() === 0)
            return;

        // Get error level
        $level = constant('self::' . strtoupper(Config::get('debug.error_level', 'debug')));

        // Get error label
        $label = isset($this->_levels[$errno]) ? $this->_levels[$errno] : $errno;

        // Replace full paths to not inform hackers
        $msg = str_replace(APP_DIR . DS, '', $errstr);
        $file = str_replace(APP_DIR . DS, '', $errfile);

        // Log error
        Log::error($label . ' : "' . $msg . '" in ' . $file . ' (ln.' . $errline . ')');

        switch ($errno)
        {
            case E_NOTICE:
            case E_WARNING:
            case E_USER_NOTICE:
            case E_USER_WARNING:
            case E_CORE_WARNING:
            case E_COMPILE_WARNING:

                // Error level is not enough to display errors
                if ($level < self::STRICT)
                    return;

                if (CLI_MODE)
                {
                    $cli = Cli::getInstance();
                    $cli->printf('[' . $label . ']', 'yellow');
                    $cli->printf(' -> "' . $msg . '" in ' . $file . ' (ln.' . $errline . ')' . PHP_EOL);
                }
                else
                {
                    echo '<code><span style="font-size:14px;"><strong style="color:#900;">' . $label . '</strong> : <strong>"' . $msg . '"</strong> in <i>' . $file . '</i> (ln.' . $errline . ')<br /></span></code>';
                }
                break;

            default:
                if (CLI_MODE)
                    $this->_showCliError($label, $message, debug_backtrace());

                $message = '"' . $msg . '" in <i>' . $file . '</i> (ln.' . $errline . ')';
                while (ob_get_level()) ob_end_clean();

                ob_start();
                    $this->_showError($label, $message, debug_backtrace());
                    $error = ob_get_contents();
                ob_end_clean();

                if ($level === self::OFF)
                {
                    $message = 'Error in '.$file . ' (ln.' . $errline . ')';
                    $this->_sendErrorByEmail($message, $error);
                    $this->_showGenericErrorPage();
                }

                die($error);             
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
        // Get error level
        $level = constant('self::' . strtoupper(Config::get('debug.error_level', 'debug')));        
        
        /* @var $exception Exception */
        $trace = $exception->getTrace();

        $t = array();
        $t['args'] = isset($trace[0]['args']) ? $trace[0]['args'] : '';
        $t['line'] = $exception->getLine();
        $t['file'] = $exception->getFile();

        $f = file($exception->getFile());

        $t['function'] = '';
        $t['exception'] = trim($f[$exception->getLine() - 1]);

        array_unshift($trace, $t);

        $class = get_class($exception);

        $message = $class . ' : "' . $exception->getMessage() . '" in ' . str_replace(APP_DIR . DS, '', $exception->getFile()) . ' (ln.' . $exception->getLine() . ')';
        Log::error($message);

        if (CLI_MODE)
            $this->_showCliError($class, $message, $trace);

        $message = '"' . $exception->getMessage() . '" in <i>' . str_replace(APP_DIR . DS, '', $exception->getFile()) . '</i> (ln.' . $exception->getLine() . ')';
        
        while (ob_get_level()) ob_end_clean();       
        
        ob_start();
            $this->_showError(get_class($exception), $message, $trace);
            $error = ob_get_contents();
        ob_end_clean();
        
        if ($level === self::OFF)
        {
            $message = 'Exception in '.str_replace(APP_DIR . DS, '', $exception->getFile()) . ' (ln.' . $exception->getLine() . ')';
            $this->_sendErrorByEmail($message, $error);
            $this->_showGenericErrorPage();
        }

        die($error);        
    }

    /**
     * Handler for fatal errors after script execution 
     */
    public function shutdownHandler()
    {
        $e = error_get_last();
        if ($e !== NULL)
        {
            // We must save the session datas before sending fatal error
            Session::init()->save();
            session_write_close();
            $this->errorHandler($e['type'], $e['message'], $e['file'], $e['line'], null);            
        }
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

        $cli->printf('[' . $type . ']', 'red');
        $cli->printf(' -> ' . $message . PHP_EOL);

        $bt = $this->_parseBacktrace($backtrace);

        $nb = count($bt);
        foreach ($bt as $k => $line)
        {
            $cli->printf('[debug' . $nb-- . ']', 'cyan');
            $cli->printf(' -> ');

            if (isset($line['exception']))
            {
                $cli->printf($line['exception']);
            }
            else
            {
                $cli->printf($line['function'] . '(' . $line['args'] . ')');
            }

            if (isset($line['line']) && isset($line['file']))
                $cli->printf(' / ' . $line['file'] . ' ln.' . $line['line']);

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
     * @return void
     */
    private function _showError($type, $message, $backtrace)
    {
        set_header(503);
        header('Retry-after: 28800');     
        
        $bt = $this->_parseBacktrace($backtrace);

        $tpl = Template::getInstance();
        $tpl->setTemplateDir(HOOKS_DIR . DS . 'errors');
        $tpl->addTemplateDir(FW_DIR . DS . 'errors');

        $tpl->assign('bt', $bt);
        $tpl->assign('nbStack', count($bt));
        $tpl->assign('type', $type);
        $tpl->assign('message', $message);

        // Get error level
        $level = constant('self::' . strtoupper(Config::get('debug.error_level', 'debug')));          
        
        $template = 'error.html';
        if ($level === self::OFF)
        {
            $template = 'error_detailed.html';
            $tpl->assign('server', $_SERVER);
            $tpl->assign('request', var_export($_REQUEST, true));
            $addr = $_SERVER['REMOTE_ADDR'];
            $host = gethostbyaddr($_SERVER['REMOTE_ADDR']);
            if($host !== $addr) $addr = $host.' ('.$addr.')';
            $tpl->assign('addr', $host);
        }          
        
        try
        {
            $tpl->setTemplate($template)->render();
        }
        catch (Exception $exc)
        {
            echo $exc->getMessage();
        }
    }
    
    /**
     * Send the error page by e-mail
     * 
     * @param string $message
     * @param string $error
     */
    private function _sendErrorByEmail($message, $error)
    {
        $email = Config::get('debug.notification_email');        
        if($email === false || $email === '' || !String::checkEmail($email)) return;
        
        $sender = Config::get('debug.notification_sender', 'noreply@'.$_SERVER['HTTP_HOST']);
        
        Email::to($email)->from($sender)
                         ->subject($message)
                         ->bodyHtml($error)
                         ->bodyText(strip_tags($error))
                         ->send();
    }
    
    /**
     * Stop script and show an generic error page
     * 
     * @return void
     */
    private function _showGenericErrorPage()
    {
        while (ob_get_level()) ob_end_clean();
        
        set_header(503);
        header('Retry-after: 28800');

        $tpl = Template::getInstance();
        $tpl->setTemplateDir(HOOKS_DIR . DS . 'errors');
        $tpl->addTemplateDir(FW_DIR . DS . 'errors');

        try
        {
            $tpl->setTemplate('generic.html')->render();
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

        if (empty($bt))
            return $result;

        foreach ($bt as $k => $v)
        {
            if (isset($v['class']) && $v['class'] === get_class())
                continue;

            $r = array();

            $r['function'] = isset($v['class']) ? $v['class'] . $v['type'] . $v['function'] : $v['function'];

            if (isset($v['exception']))
                $r['exception'] = $v['exception'];

            if (!empty($v['args']))
            {
                $args = array();
                foreach ($v['args'] as $arg)
                {

                    $a = $arg;
                    if ($arg === null)
                        $a = 'null';
                    if (is_string($arg))
                        $a = "'" . $arg . "'";
                    if (is_object($arg))
                        $a = "object('" . get_class($arg) . "')";
                    if (is_array($arg))
                        $a = 'array()';
                    $args[] = $a;
                }

                $r['args'] = join(', ', $args);
            }
            else
            {
                $r['args'] = '';
            }

            if (isset($v['line']) && isset($v['file']))
            {
                $r['line'] = $v['line'];
                $r['file'] = str_replace(APP_DIR . DS, '', $v['file']);
            }

            if (!empty($v['file']) && is_file($v['file']))
            {
                $f = file($v['file']);
                $r['code'] = isset($f[$v['line'] - 1]) ? trim($f[$v['line'] - 1]) : '';

                $nbLines = 9;
                $offset = $v['line'] - ceil($nbLines / 2) < 0 ? 0 : $v['line'] - ceil($nbLines / 2);

                foreach (array_slice($f, $offset, $nbLines) as $k => $line)
                {
                    $line = str_replace(PHP_EOL, '', $line);
                    $str = highlight_string('<?php ' . $line . ' ?>', true);
                    $str = preg_replace('#(&lt;\?.*?)(php)?(.*?&nbsp;)#s', '', $str);
                    $str = preg_replace('#(\?&gt;)#s', '', $str);
                    $str = str_replace(array('<code>', '</code>'), array('', ''), $str);

                    $r['fragment'][++$offset] = $str;
                }
            }

            $result[] = $r;
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
        while (ob_get_level()) ob_end_clean();
        set_header(401);

        $uri = Uri::getInstance()->getUri(true);

        $tpl = Template::getInstance();
        $tpl->setTemplateDir(HOOKS_DIR . DS . 'errors');
        $tpl->addTemplateDir(FW_DIR . DS . 'errors');

        Log::error('401 - Authorization error : ' . $uri);

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
        while (ob_get_level()) ob_end_clean();
        set_header(404);

        $uri = $_SERVER['REQUEST_URI'];

        $tpl = Template::getInstance();
        $tpl->setTemplateDir(HOOKS_DIR . DS . 'errors');
        $tpl->addTemplateDir(FW_DIR . DS . 'errors');

        $tpl->assign('uri', $uri);

        Log::error('404 - File not found : ' . $uri);

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
     * Stop script and show a configuration error page
     * 
     * @return void
     */
    public static function showConfigurationError()
    {
        while (ob_get_level()) ob_end_clean();
        set_header(404);

        $uri = Uri::getInstance()->getUri(false);

        $tpl = Template::getInstance();
        $tpl->setTemplateDir(HOOKS_DIR . DS . 'errors');
        $tpl->addTemplateDir(FW_DIR . DS . 'errors');

        $tpl->assign('uri', $uri);
        $tpl->assign('routeFilePath', trim(CACHE_DIR . DS . 'routes.xml'));

        $tpl->assign('tests', self::environmentTests());

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

    private static function environmentTests()
    {
        $tests = array();

        // PHP version
        $test['label'] = 'PHP';
        $test['value'] = PHP_VERSION;
        $test['pass'] = version_compare(PHP_VERSION, '5.2.0', '>');
        $test['info'] = version_compare(PHP_VERSION, '5.3.0', '<') ? 'PHP >= 5.3 recommanded' : null;
        $tests[] = $test;

        // Configuration
        $test['label'] = 'Configuration environment (APP_ENV)';
        $test['value'] = Config::getEnvironment();
        $test['pass'] = true;
        $test['info'] = null;
        $tests[] = $test;

        // Dirs
        $test['label'] = 'Project Directory (APP_DIR)';
        $test['value'] = APP_DIR;
        $test['pass'] = true;
        $test['info'] = null;
        $tests[] = $test;

        $t = is_writable(CACHE_DIR) && is_writable(CACHE_DIR . DS . 'templates_c') && is_writable(CACHE_DIR . DS . 'html');
        $test['label'] = 'Cache Directory (CACHE_DIR)';
        $test['value'] = CACHE_DIR;
        $test['pass'] = $t;
        $test['info'] = !$t ? 'Cache directory or one of his subdir is not writeable' : null;
        $tests[] = $test;

        $t = is_writable(LOGS_DIR);
        $test['label'] = 'Logs Directory (LOGS_DIR)';
        $test['value'] = LOGS_DIR;
        $test['pass'] = $t;
        $test['info'] = !$t ? 'Logs directory is not writeable' : null;
        $tests[] = $test;

        // required extensions
        $exts = array('GD', 'mbstring', 'PDO');
        foreach ($exts as $ext)
        {
            $t = extension_loaded($ext);
            $test['label'] = $ext;
            $test['value'] = $t ? 'Pass' : 'Fail';
            $test['pass'] = $t;
            $test['info'] = !$t ? $ext . ' extension is not loaded' : null;
            $tests[] = $test;
        }

        // required extensions
        $exts = array('APC', 'mcrypt');
        foreach ($exts as $ext)
        {
            $t = extension_loaded($ext);
            $test['label'] = $ext . ' enabled';
            $test['value'] = $t ? 'Pass' : 'Recommanded';
            $test['pass'] = $t;
            $test['info'] = !$t ? $ext . ' extension is recommanded' : null;
            $tests[] = $test;
        }

        $t = extension_loaded('memcache') || extension_loaded('memcached');
        $test['label'] = 'memcache(d) enabled';
        $test['value'] = $t ? 'Pass' : 'Recommanded';
        $test['pass'] = $t;
        $test['info'] = !$t ? 'memcached extension is recommanded' : null;
        $tests[] = $test;

        return $tests;
    }

}