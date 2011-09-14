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

/**
 * Class to search files/directories recursively
 * 
 * @example Search::file('*.php')->in(MODULES_DIR)->setDepth(1,2)->fetch();
 */

class Search
{
    private $_type;
    private $_folder;
    private $_maxDepth = -1;
    private $_minDepth = 0;
    private $_exclude = array('.svn', '_svn', 'cvs', '.bzr', '.git', '.hg');
    private $_patterns = array();
    
    /**
     * Main constructor
     * @param string $type      Type of search, can be 'file', 'dir' or 'any'
     */
    private function __construct($type)
    {
        $this->_type = $type;
    }
    
    /**
     * Get a new search instance for files
     * 
     * @param $args     Set glob args to search
     * @return Search   New instance of Search
     */
    public static function file()
    {
        $inst = new self('file');        
        if(func_num_args() == 0) $inst->_glob('*.*');        
        foreach(func_get_args() as $k) $inst->_glob(trim($k));        
        return $inst;        
    }
    
    /**
     * Get a new search instance for directories
     * 
     * @param $args     Set glob args to search
     * @return Search   New instance of Search
     */
    public static function dir()
    {
        $inst = new self('dir');        
        if(func_num_args() == 0) $inst->glob('*');        
        foreach(func_get_args() as $k) $inst->_glob(trim($k));        
        return $inst;        
    }    
    
    /**
     * Get a new search instance for files and directories
     * 
     * @param $args     Set glob args to search
     * @return Search   New instance of Search
     */    
    public static function any()
    {
        $inst = new self('any');        
        if(func_num_args() == 0) $inst->glob('*');        
        foreach(func_get_args() as $k) $inst->_glob(trim($k));        
        return $inst;        
    }    
    
    /**
     * Add a glob pattern to search
     */
    private function _glob()
    {
        $args = func_get_args();
        $this->_patterns = array_unique(array_merge($this->_patterns, $args));
    }
    
    /**
     * Set the folder to search into
     * 
     * @param string $folder    The folder as a string
     * @return Search           Current instance of Search
     */
    public function in($folder)
    {
        $this->_folder = rtrim(realpath($folder), DS);
        return $this;
    }
    
    /**
     * Set folder(s) to exclude from search, accepts multi arguments.<br />
     * By default Search excludes .svn, _svn, cvs, .bzr, .git, .hg folders
     * 
     * @example exclude('.svn', '.cvs')
     * @return Search       Current instance of Search
     */
    public function exclude()
    {
        $args = func_get_args();
        $this->_exclude = array_unique(array_merge($this->_exclude, $args));
        return $this;
    }
    
    /**
     * Sets the minimum and maximum directory depth to search into
     * 
     * @param integer $min      [optional] The minimum depth, default is 0
     * @param integer $max      [optional] The maximum depth, default is -1 (no limit)
     * @return Search           Current instance of Search
     */
    public function setDepth($min = 0, $max = -1)
    {
        $this->_minDepth = $min;
        $this->_maxDepth = $max;
        return $this;
    }    
    
    /**
     * Fetch search results into an array of paths
     * 
     * @return array        An array with files/directories paths
     */
    public function fetch($restrictToDir = PROJECT_DIR)
    {
        $result = array();
        
        // no folder... no search ! ;-)
        if(is_null($this->_folder)) trigger_error('You must define a folder to find files', E_USER_ERROR);        
        
        // folder is not in the base directory (by default the project dir)
        if(!strstr($this->_folder, $restrictToDir)) trigger_error('Directory listing is not secure, directory is outside project directory');        
        
        // instanciate the iterator
        $i = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($this->_folder), RecursiveIteratorIterator::SELF_FIRST);        
        
        // setting maximum depth
        $i->setMaxDepth($this->_maxDepth);        
        
        // create exlusion regexp
        $excludes = '#/'.join('|/', $this->_exclude).'#';

        try
        {
            foreach ($i as $k => $v)
            {     
                // if depth is setted and the current path is under the minimum depth, check next path
                if($this->_minDepth != 0 && $i->getDepth() < $this->_minDepth) continue;
                
                // path has not any excluded pattern
                if(!preg_match($excludes, $k))
                {
                    // add file/directory to list
                    if(($this->_type == 'file' && $v->isFile()) || ($this->_type == 'dir' && $v->isDir()) || $this->_type == 'any')
                    {
                        $result[] = $k;  
                    }                 
                }
            }

            // filter paths list with $this->_regexFilter()
            $result = array_filter($result, array($this, '_regexFilter'));
        }
        catch (UnexpectedValueException $e)
        {
            throw new Exception('The directory contained a directory we can not recurse into');
        }

        return $result;
    }
    
    /**
     * Array filter to check if path is correspondig to the glob pattern
     * 
     * @param string $path      Path to check
     * @return boolean          Return true if path is corresponding to the glob pattern
     */
    private function _regexFilter($path)
    {
        // foreach glob patterns
        foreach($this->_patterns as $pattern)
        {
            // convert glob to regexp and check
            if(preg_match($this->_globToRegex($pattern), $path))
            {
                return true;
            }                        
        }
        return false;
    }
    
    /**
     * Converts glob pattern to a regexp pattern.
     * 
     * Based on sfGlobToRegexp class from Symfony
     * 
     * @param string $glob      The glob pattern to convert
     * @return string           The regexp pattern
     */
    private function _globToRegex($glob)
    {
        if(preg_match('/^(!)?([^a-zA-Z0-9\\\\]).+?\\2[ims]?$/', $glob)) return $glob;
        
        $first = true;
        $esc = false;
        $open = 0;
        $regex = '';
        
        for ($i = 0; $i < strlen($glob); $i++)
 	    {
            $chr = $glob[$i];
            
            if($first)
            {
                if($chr !== '.') $regex .= '(?=[^\.])';
                $first = false;
            }
            
            switch ($chr)
            {
                case '/':
                    $first = true;
                    $regex .= $chr;         
                break;

                case '.':
                case '(':
                case ')':
                case '|':
                case '+':
                case '^':
                case '$':
                    $regex .= "\\$chr";
                break;
            
                case '*':
                    $regex .= $esc ? '\\*' : '[^/]*';
                break;
            
                case '?':
                    $regex .= $esc ? '\\?' : '[^/]';
                break;
            
                case '{':
                    $regex .= $esc ? '\\{' : '(';
                    if(!$esc) ++$open;
                break;     
                
                case '}':
                    if($open) 
                    {
                        $regex .= $esc ? '}' : ')';
                        if(!$esc) --$open;
                    }
                break;
                    
                case ',':
                    if($open)
                    {
                        $regex .= $esc ? ',' : '|';
                    }
                break;
                
                case '\\':
                    if($esc)
                    {
                        $regex .= '\\\\';
                        $esc = false;
                    }
                    else
                    {
                        $esc = true;
                    }
                break;
                
                default:
                    $regex .= $chr;                    
                break;
            }
            
            $esc = false;
        }
        
        return '#'.$regex.'$#';
    }
}