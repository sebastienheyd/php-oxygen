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

class Controller
{
	/**
	 * @var Controller
	 */
	private static $_instance;

	private $_module;
	private $_action;
	private $_className;
    private $_args = array();
	private $_chain;

	const DEFAULT_ACTION_METHOD = "execute";
	const DEFAULT_AUTHORIZATION_METHOD = "isAuthorized";
	const DEFAULT_ERRORHANDLER_METHOD = "errorHandler";

	private function __construct() {}

    /**
     * Magic method to get non existant class vars
     * 
     * @param string $name      Name of the var to get
     * @return mixed            Return the var content
     */    
    public function __get($name)
    {
        switch ($name)
        {
            case 'config':
                return Config::getInstance();
            break;
            case 'request':
                return Request::getInstance();
            break;
        }
    }

	/**
     * Get controller instance
     * 
	 * @return Controller   Return the instance of Controller (singleton)
	 */
	public static function getInstance()
	{
		if(is_null(self::$_instance))
		{
			self::$_instance = new self();
		}
		return self::$_instance;
	}   

    /**
	 * Main dispatcher
	 *
	 * @return string   Return the compiled view content
	 */
	public function dispatch()
	{
		$mv = '';
        
        ob_start();

        // Get rerouted uri or current uri
        $uri = Router::getInstance()->parseUrl();

        if(!$uri->isDefined() && Config::get('url', 'routed_only') == '1' && $uri->getUri() != '/') 
        {
            $this->_loadAsset();
            Error::show404 ();        
        }            
        
        // Action and module names are not directly specified.
        // Ex : Controller::getInstance()->setModule('default')->setAction('index')->dispatch();
		$this->_parseFromRequest($uri);

        // Convert module/action to className
        $this->_getActionClassName();

        $this->_addToChain();
                
		// If action is authorized ...
        if($this->_isAuthorized())
        {
            //... process execute()
            $mv = $this->_processAction();
        }
        else
        {
            //... process handleError()
            $mv = $this->_handleError();
        }
		$view = '';
		if(is_string($mv)) $view = $mv;
		if(is_object($mv)) $view = $this->_processView($mv);

        $this->_removeFromChain();
        if(empty($this->_chain['classNames'])) ob_end_flush ();
        
		return $view;
	}
    
// ============================================================ REQUEST PARSING    

    /**
	 * Get the parameters from HTTP request
     * 
     * @return void
	 */
	private function _parseFromRequest($uri)
	{
        // no parametrer has been directly set by setModule and setAction
        if(is_null($this->_action) && is_null($this->_module))
		{
            // @todo add possibility to disallow direct request

            // if $_GET['module'] has been set
            if(isset($this->request->get->module))
            {
                $this->_module = $this->request->get->module;
                
                // get action or return default action 
                $this->_action = ucfirst_last($this->request->get('action', 'index'));
            }
            else
            {                
                $this->_parseFromUri($uri);
            }
        }
        else
        {
             $nb = $uri->nbSegments();
             if($nb >= 1) { $this->_loadAsset(); }
             if($nb > 2 && empty($this->_args)) { $this->_args =  $uri->segmentsSlice(2); }
        }
	}
    
    /**
     * Parse module, action and args from uri
     * 
     * @param Uri $uri 
     * @return void
     */
    private function _parseFromUri($uri)
    {
        $nb = $uri->nbSegments();

        // if uri content more than one segment (module/action)
        if($nb >= 1)
        {      
            // load asset if exists
            $this->_loadAsset();
            $this->_module = $uri->segment(1);
            $this->_action = ucfirst_last($uri->segment(2, 'index'));
            $this->_args = $uri->segmentsSlice(2);
        }
        else
        {
            Error::showConfigurationError();
            die();
        }
    }
    
    /**
     * Check and returns asset content if exists (and stop script)
     * 
     * @return void
     */
    private function _loadAsset()
    {
        $uri = Uri::getInstance()->getUri(true);
        $segments = explode('/', trim($uri, '/'));
        
        $mUri = join('/', array_slice($segments, 1));
        
        if($file = File::load(FW_DIR.DS.'assets'.$uri)) $file->output();        
        if($file = File::load(WEBAPP_MODULES_DIR.DS.$segments[0].DS.'assets'.DS.$mUri)) $file->output();
        if($file = File::load(MODULES_DIR.DS.$segments[0].DS.'assets'.DS.$mUri)) $file->output();
    }
   
// ============================================================ ACTION PROCESSING 
    
    /**
     * Get action class name from called action and module
     * 
     * @return void 
     */
    private function _getActionClassName()
    {
        if(!is_null($this->_module) && !is_null($this->_action))
        {
            $className = 'm_'.$this->_module.'_action_'.$this->_action;
            if(!class_file_path($className))
            {   
                $this->_action = 'Index';
                $className = 'm_'.$this->_module.'_action_'.$this->_action;

                if(!class_file_path($className))
                {
                    Error::show404();
                }

                $this->_args = Uri::getInstance()->segmentsSlice(1);
            }
            if(!class_exists($className)) trigger_error('Class '.$className.' not found !', E_USER_ERROR);
            if(!method_exists($className, self::DEFAULT_ACTION_METHOD)) trigger_error('Method '.self::DEFAULT_ACTION_METHOD.' not found in '.$className, E_USER_ERROR);
            
            $this->_className = $className;
        }
    }     

	/**
	 * Call execute() method in the action class
	 *
	 * @return string|object    Return the compiled action result as a string or an object
	 */
	private function _processAction()
	{
		$class = new $this->_className();
        $result = call_user_func_array(array($class, self::DEFAULT_ACTION_METHOD), $this->_args);
        return is_string($result) ? $result : $class;
	}
    
	/**
	 * Call authorisation method in the action class and retrieve the result
	 *
	 * @return boolean  Return true if action is authorized to be execute
	 */
	private function _isAuthorized()
	{
		$class = new $this->_className();
        if(!method_exists($class, self::DEFAULT_AUTHORIZATION_METHOD)) return true;
		return (boolean) $class->{self::DEFAULT_AUTHORIZATION_METHOD}();
	}

	/**
	 * Call handleError() method in the action class
	 *
	 * @return string|exception     Return action content or an exception
	 */
	private function _handleError()
	{
		$class = new $this->_className();
        if(!method_exists($class, self::DEFAULT_ERRORHANDLER_METHOD)) Error::show401();        
        $execute = $class->{self::DEFAULT_ERRORHANDLER_METHOD}();
        return is_string($execute) ? $execute : $class;
	}       
    
// ============================================================ VIEW PROCESSING    

    /**
     * Get view class name from called action and module
     * 
     * @param string $model     Model returned by processAction()
     * @param type $viewName    View name like success, input, error, etc...
     * @return string           Class name
     */
    private function _getViewClassName($model, $viewName)
    {
        return str_replace('action', 'view', get_class($model)).$viewName;
    }      
    
	/**
	 * Return the view content by the model name
	 *
	 * @param string $model     The model name to output
	 * @return string           Return the processed view content
	 */
	private function _processView($model)
	{
        $viewName = '';

        if(method_exists($model, 'setView')) $viewName = ucfirst($model->getView());

        if(!is_string($viewName) || $viewName == '')  return '';

        $className = $this->_getViewClassName($model, $viewName);
		$methodName = self::DEFAULT_ACTION_METHOD;
        
        if(class_exists($className))
        {
            $class = new $className();            
        }
        else
        {
            if($this->_renderView($className, $model)) return '';
            trigger_error('Cannot load file for class '.$className, E_USER_ERROR);
        }
        
        if(!$class instanceof View) trigger_error ('Class '.$className.' does not extends View', E_USER_ERROR);
        $class->setModel($model->getModel());
        $class->setModule($this->_module);
        
        $result = $class->$methodName();
        
        return is_string($result) ? $result : ob_flush();
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
        $module = $matches[1];    
        $l = explode('_', $matches[2]);
        $filename = lcfirst(end($l)).'.html';

        if($file = get_module_file($module, 'template'.DS.$filename))
        {            
            $tpl = Template::getInstance($file, $module);
            
            $tpl->cache_lifetime = $model->cacheLifetime;
            
            // Assign all models to template
            if(is_array($model->getModel()) && count($model->getModel()) > 0)
            {
                foreach($model->getModel() as $k => $v)
                {
                    $tpl->assign($k, $v);
                }
            }

            $tpl->addTemplateDir(WEBAPP_MODULES_DIR.DS.$module.DS.'template');
            $tpl->addTemplateDir(MODULES_DIR.DS.$module.DS.'template');

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
        $this->_chain['modules'][] = $this->_module;
		$this->_chain['actions'][] = $this->_action;
		$this->_chain['classNames'][] = $this->_className;
    }

    /**
     * Remove current processed action from process chain
     */    
    private function _removeFromChain()
    {
        $modules = $this->_chain['modules'];
		$actions = $this->_chain['actions'];
		$classNames = $this->_chain['classNames'];

		$module = end($modules);
		$action = end($actions);
		$className = end($classNames);

		array_pop($this->_chain['modules']);
		array_pop($this->_chain['actions']);
		array_pop($this->_chain['classNames']);
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
		return $this;
	}
    
    public function setArgs($args)
    {
        $this->_args = $args;
        return $this;
    }

	/**
	 * Redirect to an http url
	 *
	 * @param string $url   The http url to redirect to
	 * @return void
	 */
	public static function redirect($url)
	{
        while (ob_get_level()) { ob_end_clean(); }         
        $url = str_ireplace("location:", "", $url);
        $url = str_ireplace("http://", "", $url);
        header("Location: http://".$url, true);
        exit;     
	}
}