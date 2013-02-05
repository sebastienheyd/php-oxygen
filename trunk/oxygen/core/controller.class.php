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
class Controller
{

    /**
     * @var Controller
     */
    private static $_instance;
    private $_module;
    private $_action;
    private $_className;
    private $_classInstance;
    private $_args = array();
    private $_chain;
    private $_explicit;

    const DEFAULT_ACTION_METHOD        = 'execute';
    const DEFAULT_AUTHORIZATION_METHOD = 'isAuthorized';
    const DEFAULT_ERRORHANDLER_METHOD  = 'errorHandler';
    const DEFAULT_MODULE_ACTION        = 'Index';

    private function __construct() {}

    /**
     * Get controller instance
     * 
     * @return Controller   Return the instance of Controller (singleton)
     */
    public static function getInstance()
    {
        return new self();
    }

    /**
     * Main dispatcher
     *
     * @return string   Return the compiled view content
     */
    public function dispatch()
    {
        ob_start();

        // Module and action are not defined by setters
        if (!$this->_explicit)
        {
            // Get uri from routes
            $uri = Route::getInstance()->parseUrl();

            // No uri found, try to load asset
            if (!$uri->isDefined() && Config::get('route.routed_only') == '1' && $uri->getUri(false) != '/')
            {
                $this->_loadAsset();
                Error::show404();
            }

            // Get module and action from route
            $this->_parseFromRequest($uri);
        }

        // Convert module/action to className
        $this->_getActionClassName();

        // Add to chaining for logs
        $this->_addToChain();

        // Initialize result
        $mv = '';
        
        // If action is authorized ...
        if ($this->_isAuthorized())
        {
            //... process execute()
            $mv = $this->_processAction();
        }
        else
        {
            //... else process handleError()
            $mv = $this->_handleError();
        }

        // Action method result is an object, render view
        if (is_object($mv)) $mv = $this->_processView($mv);
        
        // Display to browser
        echo $mv;

        // Remove action from chain for logs
        $this->_removeFromChain();
        
        // No more actions in chain, render to browser
        if (empty($this->_chain['classNames'])) ob_end_flush();
    }

// ============================================================ REQUEST PARSING    

    /**
     * Get the parameters from HTTP request
     * 
     * @param Uri $uri      Instance of Uri
     * @return void
     */
    private function _parseFromRequest(Uri $uri)
    {
        Log::debug('{Controller->_parseFromRequest()} ' . $uri->getUri(false));

        // no parametrer has been directly set by setModule and setAction
        if ($this->_action === null && $this->_module === null)
        {
            $request = Request::getInstance();

            // if $_GET['module'] has been set
            if ($module = $request->get('module'))
            {
                $this->_module = $module;

                // get action or return default action 
                $this->_action = ucfirst_last($request->get('action', 'index'));
            }
            else
            {
                $this->_parseFromUri($uri);
            }
        }
        else
        {
            $nb = $uri->nbSegments();
            if ($nb >= 1) $this->_loadAsset();
            if ($nb > 2 && empty($this->_args)) $this->_args = $uri->segmentsSlice(3);
        }
    }

    /**
     * Parse module, action and args from uri
     * 
     * @param Uri $uri 
     * @return void
     */
    private function _parseFromUri(Uri $uri)
    {
        Log::debug('{Controller->_parseFromUri()} ' . $uri->getUri(false));

        if ($uri->nbSegments() === 0) Error::showConfigurationError();

        // load asset if exists
        $this->_loadAsset();
        $this->_module = strtolower($uri->segment(1));
        $this->_action = ucfirst_last($uri->segment(2, 'index'));
        $this->_args = $uri->segmentsSlice(3);
    }

    /**
     * Check and returns asset content if exists (and stop script)
     * 
     * @return void
     */
    private function _loadAsset()
    {
        $uri      = Uri::getInstance()->getUri();
        $segments = explode('/', trim($uri, '/'));

        if (!empty($segments))
        {
            if (preg_match('#(.js|.css)$#', $segments[0]))
            {
                $f    = CACHE_DIR . DS . 'merged' . DS . $segments[0];
                if ($file = File::load($f))
                {
                    Log::info('{Controller->_loadAsset()} ' . $f);
                    $file->output();
                }
            }
        }

        $mUri = join('/', array_slice($segments, 1));

        $paths = array(
            FW_DIR . DS . 'assets' . $uri,
            WEBAPP_MODULES_DIR . DS . $segments[0] . DS . 'assets' . DS . $mUri,
            MODULES_DIR . DS . $segments[0] . DS . 'assets' . DS . $mUri
        );

        foreach ($paths as $f)
        {
            if ($file = File::load($f))
            {
                Log::info('{Controller->_loadAsset()} ' . $f);
                $file->output();
            }
        }
    }

// ============================================================ ACTION PROCESSING 

    /**
     * Get action class name from called action and module
     * 
     * @return void 
     */
    private function _getActionClassName()
    {
        if ($this->_module !== null)
        {
            // Action is not defined, 
            if ($this->_action === null) $this->_action = self::DEFAULT_MODULE_ACTION;
                
            // Build class Name
            $className = 'm_' . $this->_module . '_action_' . $this->_action;
            
            // Check if class exists
            if (!class_exists($className)) throw new RuntimeException('Class ' . $className . ' not found !', E_USER_ERROR);            
            
            // Check if default action method exists
            if (!method_exists($className, self::DEFAULT_ACTION_METHOD))
                trigger_error('Method ' . self::DEFAULT_ACTION_METHOD . ' not found in ' . $className, E_USER_ERROR);
            
            // Add args to default method
            if ($this->_args === null) $this->_args = Uri::getInstance()->segmentsSlice(2);
            
            // Set class name
            $this->_className = $className;
            
            // Instanciate action class
            $this->_classInstance = new $this->_className();
        }
    }

    /**
     * Call authorisation method in the action class and retrieve the result
     *
     * @return boolean  Return true if action is authorized to be execute
     */
    private function _isAuthorized()
    {
        Log::debug('{Controller->_isAuthorized()} ' . $this->_className . '->' . self::DEFAULT_AUTHORIZATION_METHOD . '()');       
        
        // If no authorization method is found, return true by default
        if (!method_exists($this->_classInstance, self::DEFAULT_AUTHORIZATION_METHOD)) return true;
        
        // If authorization method is found, return method result
        $result = $this->_classInstance->{self::DEFAULT_AUTHORIZATION_METHOD}();
        
        // Check return method value
        if(!is_bool($result)) return false;
        return $result;
    }

    /**
     * Call execute() method in the action class
     *
     * @return string|object    Return the compiled action result as a string or an object
     */
    private function _processAction()
    {
        Log::debug('{Controller->_processAction()} ' . $this->_className . '->' . self::DEFAULT_ACTION_METHOD . '()');
        
        // Call default action method
        $result = call_user_func_array(array($this->_classInstance, self::DEFAULT_ACTION_METHOD), $this->_args);
        
        // If method result is a string return it else return the instance
        return is_string($result) ? $result : $this->_classInstance;
    }
    
    /**
     * Call handleError() method in the action class
     *
     * @return string|exception     Return action content or an exception
     */
    private function _handleError()
    {
        Log::debug('{Controller->_handleError()} ' . $this->_className . '->' . self::DEFAULT_ERRORHANDLER_METHOD . '()');
        
        // No default error handler method in action, return a 401 error
        if (!method_exists($this->_classInstance, self::DEFAULT_ERRORHANDLER_METHOD)) Error::show401();
        
        // Else execute error handler method
        $execute = $this->_classInstance->{self::DEFAULT_ERRORHANDLER_METHOD}();
        
        // If method result is a string return it else return the instance
        return is_string($execute) ? $execute : $this->_classInstance;
    }

// ============================================================ VIEW PROCESSING    

    /**
     * Return the view content by the model name
     *
     * @param string $model     The model name to output
     * @return string           Return the processed view content
     */
    private function _processView(Action $model)
    {
        $viewName = $model->view;

        if (!is_string($viewName) || $viewName === '') return '';

        if(!$file = get_module_file($this->_module, 'template' . DS . $viewName))
        {
            $viewName = lcfirst($this->_action).ucfirst($viewName).'.html';
            $file = get_module_file($this->_module, 'template' . DS . $viewName);
        }
  
        if(!$file && is_file($model->view)) $file = $model->view;
        
        if(!$file) trigger_error($model->view.' not found in module '.$this->_module, E_USER_ERROR);  
        
        $tpl = Template::getInstance($file, $this->_module);

        $tpl->cache_lifetime = $model->cacheLifetime;

        // Assign all models to template
        if (is_array($model->model) && !empty($model->model))
        {
            foreach ($model->model as $k => $v) $tpl->assign($k, $v);
        }

        return $tpl->get($model->cacheId);
    }

    /**
     * Return the view content by the class name and model name
     *
     * @param string $className The class name
     * @param string $model     The model name to output
     * @return string           Return the processed view content
     */
    private function _renderView($className, $model)
    {
        preg_match('/^m_(.*)_view_(.*)/', $className, $matches);
        $module   = $matches[1];
        $l        = explode('_', $matches[2]);
        $filename = lcfirst(end($l)) . '.html';

        if ($file = get_module_file($module, 'template' . DS . $filename))
        {
            $tpl = Template::getInstance($file, $module);

            $tpl->cache_lifetime = $model->cacheLifetime;

            $modelContent = $model->getModel();

            // Assign all models to template
            if (is_array($modelContent) && !empty($modelContent))
            {
                foreach ($modelContent as $k => $v)
                    $tpl->assign($k, $v);
            }

            $tpl->render($model->cacheId);
            return true;
        }

        return false;
    }

// ============================================================ CHAINING

    /**
     * Add current processed action to process chaining
     */
    private function _addToChain()
    {
        Log::info('{Controller} Call ' . $this->_className);
        $this->_chain['modules'][] = $this->_module;
        $this->_chain['actions'][] = $this->_action;
        $this->_chain['classNames'][] = $this->_className;
        $this->_chain['timestamps'][] = microtime(true);
    }

    /**
     * Remove current processed action from process chain
     */
    private function _removeFromChain()
    {
        $modules    = $this->_chain['modules'];
        $actions    = $this->_chain['actions'];
        $classNames = $this->_chain['classNames'];
        $timestamps = $this->_chain['timestamps'];

        $module    = end($modules);
        $action    = end($actions);
        $className = end($classNames);
        $timestamp = end($timestamps);

        array_pop($this->_chain['modules']);
        array_pop($this->_chain['actions']);
        array_pop($this->_chain['classNames']);
        array_pop($this->_chain['timestamps']);

        $time = round((microtime(true) - $timestamp) * 1000, 2);
        Log::info('{Controller} [' . $time . 'ms] End of ' . $className);
    }

// ============================================================ SETTERS & OTHER PUBLIC METHODS 

    /**
     * Set the module to launch
     *
     * @param string $moduleName    Module name
     * @return Controller           Return current Controller instance
     */
    public function setModule($moduleName)
    {
        $this->_module = $moduleName;
        $this->_explicit = true;
        return $this;
    }

    /**
     * Set the action to launch
     *
     * @param string $actionName    Action name
     * @return Controller           Return current Controller instance
     */
    public function setAction($actionName)
    {
        $this->_action = ucfirst_last($actionName);
        $this->_explicit = true;
        return $this;
    }

    /**
     * Set args to explicit module/action
     * 
     * @param mixed $args       Args as function args
     * @return Controller       Return current Controller instance
     */
    public function setArgs($args)
    {
        $this->_args = is_array($args) ? $args : func_get_args();
        return $this;
    }

}