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

abstract class Document
{
    // Add extra parameters to the instancied document
    private static $extraParams = array();        
       
    /**
     * Load a document with this class from the database
     * 
     * @param string $args      Argument(s) to use for loading, can be multiple (multiple method arguments comma separated)
     * @return object           Return a new instance of the current document
     */
    protected static function _load($args)
    {
        $args = func_get_args();
        
        if(count($args) <= 1) trigger_error('load : empty arg', E_USER_ERROR);
                
        $className = array_pop($args);  
                        
        list($className, $tableName, $primary) = self::getVars($className);

        if(count($primary) > count($args)) trigger_error('load : args number not corresponding to primary keys number', E_USER_ERROR);        
                        
        if(count($primary) == 1 && !empty($args))
        {
            return DB::select()->from($tableName)->where($primary[0], $args[0])->fetchObject($className);
        }
        
        $q = DB::select()->from($tableName);
        
        foreach($primary as $k => $v)
        {
            $q->where($v, $args[$k]);
        }
        
        return $q->fetchObject($className);
    }

    /**
     * Load all documents with this class from the database
     * 
     * @param string $className         Class name of the documents to get
     * @param string $order             [optional] Order by this field. Default is null.
     * @param string $orderDirection    [optional] Order direction. Default is 'ASC'
     * @return type 
     */
    protected static function _loadAll($className, $order = null, $orderDirection = 'ASC')
    {
        list($className, $table) = self::getVars($className);
        
        $sel = DB::select()->from($table);
        
        if(!is_null($order))
        {
            $sel->orderBy($order, $orderDirection);
        }
        
        return $sel->fetchAllObject($className);
    }
    
    /**
     * Start a documents selection request
     * 
     * @return f_db_Select      An instance of f_db_Select 
     */
    public static function select()
    {
        list($className, $table) = self::getVars(get_called_class());
        return DB::select()->from($table);
    }

    /**
     * Count all documents entries in database for the current document type
     * 
     * @return integer      Number of entries
     */
    public static function countAll()
    {
        list($className, $table) = self::getVars(get_called_class());
        return DB::select()->from($table)->execute()->count();
    }

    /**
     * Delete all entries in the database table for this document (keep auto_increment value)
     * 
     * @return integer      Number of deleted entries 
     */
    public static function deleteAll()
    {
        list($className, $table) = self::getVars(get_called_class());
        return DB::getInstance()->deleteAll($table);
    }

    /**
     * Delete all entries in the database table for this document (reset auto_increment value)
     * 
     * @return integer      Number of removed lines (only with InnoDb)
     */
    public static function truncate()
    {
        list($className, $table) = self::getVars(get_called_class());
        return DB::getInstance()->truncate($table);
    }
    
    /**
     * Delete current document entry in database table
     * 
     * @return boolean  Return true if success 
     */
    public function delete()
    {           
        if(method_exists($this, 'preDelete')) 
        {
            if(!$this->preDelete()) return false;
        }
        
        $q = DB::deleteFrom($this->getTableName());
        
        foreach($this->getPrimary() as $v)
        {
            $q->where($v, $this->$v);
        }                
        
        $q->execute();
        
        if(method_exists($this, 'postDelete')) $this->postDelete();
        
        foreach(get_object_vars($this) as $k => $v)
        {
            $this->$k = null;
        }
        
        return true;
    }
    
    /**
     * Destroy current document instance
     * 
     * @param Document $object      Instance of Document
     */
    private static function _destroy($object)
    {
        $object = null;
    }

    /**
     * Get instanciated class informations
     * 
     * @param string $className     Name of the class
     * @return array                Array of object information
     */
    private static function getVars($className)
    {
        $vars = array();
        
        $class = new $className;
        
        $vars[] = $className;
        $vars[] = $class->getTableName();
        $vars[] = $class->getPrimary();

        $class = null;
        
        return $vars;
    }
    
    /**
     * Save document values in database
     * 
     * @param string $config    [optional] Config to use from the current config file. Default is "default"
     * @return boolean          Return true if success 
     */
    public function save($config = 'db1')
    {
        if(method_exists($this, 'preSave')) 
        {
            if(!$this->preSave()) return false;
        }
        
        if($this->_isUpdate())
        {
            DB::update($this->getTableName(), $this->_getUpdateVars())->where($this->_getPrimaryValues())->execute($config);
        }
        else
        {
            DB::insert($this->getTableName(), get_object_vars($this), $config);
        }
        
        if(method_exists($this, 'postSave')) $this->postSave();

        return true;
    }
    
    /**
     * This object must be saved or updated ?
     * 
     * @return boolean  Return false if document must be updated, true if not (simple saving)
     */
    private function _isUpdate()
    {
        if(isset($this->__new) && $this->__new == true) return false;
        
        $keys = $this->getPrimary();
        
        foreach($keys as $k)
        {
            if(is_null($this->$k)) return false;
        }
        
        return true;
    }
    
    /**
     * Return primary keys values
     * 
     * @return array    An associative array (field=>value)
     */
    private function _getPrimaryValues()
    {
        $res = array();
        
        foreach($this->getPrimary() as $k => $v)
        {
            $res[$v] = $this->$v;
        }

        return $res;
    }
    
    /**
     * Get current object vars to insert into db, without primary keys
     * 
     * @return array    An associative array filled with document values
     */
    private function _getUpdateVars()
    {
        $res = array();
        
        foreach(get_object_vars($this) as $k => $v)
        {
            if(!in_array($k, $this->getPrimary())) $res[$k] = $v;
        }
        
        return $res;
    }
    
    /**
     * Magic method to set non existent var in document (extra parameter)
     * 
     * @param string $property      Variable name to define
     * @param mixed $value          Variable value
     */
    public function __set($property, $value)
    {
        self::$extraParams[spl_object_hash($this)][$property] = $value;
    }
    
    /**
     * Magic method to get extra parameter value
     * 
     * @param string $property      Variable name to get
     * @return mixed                Variable value
     */
    public function __get($property)
    {
        if(isset(self::$extraParams[spl_object_hash($this)][$property])) return self::$extraParams[spl_object_hash($this)][$property];
    }
    
    /**
     * Magic method when calling inexistant class methods. In this case only work with set methods
     * 
     * @param string $name      Name of the called method
     * @param mixed $args       Arguments of the method
     */
    public function __call($name, $args)
    {
        $prefix = substr($name, 0, 3);
        $suffix = substr($name, 3);
        
        switch($prefix)
        {
            case 'set':
                $varName = String::snakeCase($suffix);
                $this->$varName = $args;
            break;
        
            default:
                throw new BadMethodCallException('Method '.$name.' does not exist');
            break;        
        }
    }
    
    /**
     * Get an stdClass object with current object variables
     * @return stdClass 
     */
    public function getObjectVars()
    {
        return to_object(get_object_vars($this));
    }
}