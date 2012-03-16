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

class Route
{
    private static $_instance;    
    
    /**
     * Get instance of Router
     * 
     * @return Router       Get current instance of router (singleton)
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
     * Parse url and reroute with routes.xml rules if found
     * 
     * @return Uri      Return the instance of Uri (rerouted or not) 
     */
    public function parseUrl()
    {
        // Retrieve all routes.xml files from modules or modules overload
        $search = Search::file('config/routes.xml')->setDepth(2,2);
        $files = array_merge($search->in(MODULES_DIR)->fetch(), $search->in(WEBAPP_MODULES_DIR)->fetch());
        
        // Retrieve from webapp/config folder
        if(is_file(WEBAPP_DIR.DS.'config'.DS.'routes.xml'))
        {
            $files[] = WEBAPP_DIR.DS.'config'.DS.'routes.xml';
        }
        
        // Get new instance of Uri
        $uriInst = Uri::getInstance();

        // Init vars
        $lastModified = 0;
        $result = array();
        $defaultRedirect = null;
        
        // There is route files
        if(!empty($files))
        {
            // Get the last modification timestamp from all files
            foreach($files as $file)
            {
                $lastModified = max($lastModified, filemtime($file));
            }
            
            // Get cache file
            $cacheFile = WEBAPP_DIR.DS.'cache'.DS.'routes.xml';
            if(!is_file($cacheFile) || filemtime($cacheFile) != $lastModified)
            {
                // Rebuild if is too old
                $this->_buildRouteCacheFile($files);
            }
            
            // Load routes rules from cache file
            $routes = simplexml_load_file($cacheFile);
            
            // Remove first / from uri
            $uri = trim($uriInst->getUri(), '/');
            
            // Parse routes
            foreach($routes as $route)
            {
                // Converts shortcuts to regexp
                $rule = str_replace(':any', '.+', str_replace(':num', '[0-9]+', $route->attributes()->rule));
  
                // Rule match to uri
                if(preg_match('#^'.$rule.'$#', $uri))
                {
                    // Is there any back reference
                    if(strpos($route->attributes()->redirect, '$') !== false && strpos($rule, '(') !== false)
                    {
                        $uri = preg_replace('#^'.$rule.'$#', $route->attributes()->redirect, $uri);
                    }
                    else
                    {
                        $uri = $route->attributes()->redirect;
                    }
                    
                    // Define rerouted uri to current instance of Uri
                    $uriInst->setUri($uri);
                }
                
                if($rule == 'default')
                {
                    $defaultRedirect = (string) $route->attributes()->redirect.'/'.$uri;
                }
            }
            
            if(!$uriInst->isDefined() && !is_null($defaultRedirect))
            {
                $uriInst->setUri($defaultRedirect);
            }
        }        

        return $uriInst;
    }
    
    /**
     * Return a route by his id
     * 
     * @param string $id        Route id
     * @param string ...        [optional] Route args
     * @return string           Uri
     */
    public static function byId($id)
    {
        $args = func_get_args();
        
        unset($args[0]);
        
        $cacheFile = WEBAPP_DIR.DS.'cache'.DS.'routes.xml';
        
        if(!is_file($cacheFile)) trigger_error('No route file');
        
        $routes = simplexml_load_file($cacheFile);
        
        $route = $routes->xpath('//route[@id="'.$id.'"]');
        
        if(empty($route)) return '';
        
        if(count($route) > 1) trigger_error('Found more than one route with id '.$id.' in routes.xml');
        
        $rule = str_replace(':any', '.+', str_replace(':num', '[0-9]+', $route[0]->attributes()->rule));
        
        $redirect = preg_replace('#\(.*?\)#i', '|', $rule);
        
        if(count($args) == 0) return '/'.$redirect;
        
        $segments = explode('/', $redirect);
        
        $res = array();
        foreach ($segments as $k => $s)
        {
            if($s == '|')
            {
                $res[] = $args[$k];
            }
            else 
            {
                $res[] = $s;
            }
        }
        
        $uri = join('/', $res);
        
        if(preg_match('#'.$rule.'#', $uri)) return '/'.$uri;
        
        trigger_error('Not enough arguments to get route');
    }
    
    /**
     * Build a xml cache file with all modules routes rules
     * 
     * @param array $files      Array of files paths to parse
     */
    private function _buildRouteCacheFile($files)
    {
        $cacheFile = WEBAPP_DIR.DS.'cache'.DS.'routes.xml';
        
        $modules = array();

        // Start a new XML structure
        $result = new XMLWriter();
        $result->openMemory();
        $result->setIndent(true);
        $result->setIndentString('    ');
        $result->startDocument('1.0','UTF-8');
        
        // Start <routes>
        $result->startElement('routes');
        
        $lastModified = 0;
        
        foreach($files as $file)
        {
            // Get the last modification timestamp to set cache file timestamp
            $lastModified = max($lastModified, filemtime($file));
            
            // File is in webapp
            if(strncasecmp(WEBAPP_MODULES_DIR, $file, strlen(WEBAPP_MODULES_DIR)) == 0)
            {
                $segments = explode(DS, str_replace(WEBAPP_MODULES_DIR.DS, '', $file));
                $module = first($segments); 
                $inWebapp = true;
            }
            else
            {
                $segments = explode(DS, str_replace(MODULES_DIR.DS, '', $file));
                $module = first($segments);       
                $inWebapp = false;
            }            
            
            // Module routes file has not already be parsed
            if(!in_array($module, $modules))
            {
                array_push($modules, $module);
                
                $xml = simplexml_load_file($file);                                
                
                $mod = $inWebapp ? 'webapp/module/'.$module.'/config/routes.xml' : 'module/'.$module.'/config/routes.xml';
                $result->writeComment(' '.$mod.' ');
                
                // Write route rule
                foreach($xml->xpath('//route') as $route)
                {
                    $result->startElement('route');

                    $result->writeAttribute('rule', $route->attributes()->rule);
                    $result->writeAttribute('redirect', $route->attributes()->redirect);
                    
                    if(isset($route->attributes()->id))
                    {
                        $result->writeAttribute('id', $route->attributes()->id);
                    }
                    
                    $result->endElement();
                }
            }
        }
        
        // </routes>
        $result->endElement();
        
        // End document    
        $result->endDocument();
        
        // Get content and put contents into the cache file
        file_put_contents($cacheFile, $result->outputMemory(true));
        
        // Set cache file modification date
        touch($cacheFile, $lastModified);
    }
}