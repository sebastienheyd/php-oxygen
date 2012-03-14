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

class f_db_Delete extends f_db_Where
{
    private $_from = array();

    /**
     * @return f_db_Delete
     */
    public static function getInstance()
    {
        return new self();
    }

    /**
     * Define from which database you want to delete values
     *
     * @param string $table     The table name
     * @return f_db_Delete      Return current instance of f_db_Delete
     */
    public function from($table)
    {
        $this->_from = $table;
        return $this;
    }  
    
    /**
     * Add a where condition
     *
     * @param string $key       Column to filter on
     * @param mixed $value      [optional] Value to search, set it to null if values are directly setted in $key. Default is null
     * @param string $type      [optional] Type of request AND or OR. Default is 'AND'
     * @return f_db_Delete      Current instance of f_db_Delete
     */    
    public function where($key, $value = null, $type = 'AND')
    {
        return parent::where($key, $value, $type);
    }   
    
    /**
     * Add a IN condition
     * 
     * @param string $field     Field name to filter
     * @param array $values     Array or comma separated list of values to check if present in field
     * @param string $type      [optional] Type of request AND or OR. Default is 'AND'
     * @return f_db_Delete      Current instance of f_db_Delete
     */
    public function whereIn($field, $values, $type = 'AND')
    {
        return parent::whereIn($field, $values, $type);
    }  
    
    /**
     * Add a NOT IN condition
     * 
     * @param string $field     Field name to filter
     * @param array $values     Array or comma separated list of values to check if present in field
     * @param string $type      [optional] Type of request AND or OR. Default is 'AND'
     * @return f_db_Delete      Current instance of f_db_Delete
     */
    public function whereNotIn($field, $values, $type = 'AND')
    {
        return parent::whereNotIn($field, $values, $type);
    }

    /**
     * Build the select request to execute
     *
     * @param string $config    [optional] Config to use from the current config file. Default is "db1"
     * @return integer          Return the number of affected rows
     */
    public function execute($config = 'db1')
    {        
        $sql  = 'DELETE FROM '.$this->_from.' ';     

        $sql .= $this->_buildWhere();
        
        $sql = trim($sql);

        $q = DB::query($sql, $config)->execute($this->_vars);
        
        return is_object($q) ? $q->count() : 0;
    }       
}