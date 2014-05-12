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

class f_db_Update extends f_db_Where
{
    private $_table;
    private $_values;
   
    /**
     *
     * @param string $table     The table name
     * @param array $values     Associative array of values to update
     * @return f_db_Update      Return a new instance of f_db_Update
     */
    public static function getInstance($table , array $values)
    {
        return new self($table, $values);
    }

    /**
     * Main constructor 
     * 
     * @param string $table     The table name
     * @param array $values     Associative array of values to update
     */
    private function __construct($table, array $values)
    {
        $this->_table = $table;
        $this->_values = $values;
    }     
    
    /**
     * Add a where condition
     *
     * @param string $key       Column to filter on
     * @param mixed $value      [optional] Value to search, set it to null if values are directly setted in $key. Default is null
     * @param string $type      [optional] Type of request AND or OR. Default is 'AND'
     * @return f_db_Update      Current instance of f_db_Update
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
     * @return f_db_Update      Current instance of f_db_Update
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
     * @return f_db_Update      Current instance of f_db_Update
     */
    public function whereNotIn($field, $values, $type = 'AND')
    {
        return parent::whereNotIn($field, $values, $type);
    }     
        
    /**
     * Execute the current update builded query
     * 
     * @param string $config    [optional] Config to use from the current config file. Default is "db1"
     * @return integer          Return the number of affected rows
     */
    public function execute($config = 'db1')
    {
        $sql = 'UPDATE '.DB::quoteTable($this->_table, $config).' SET ';
        
        $i = 0;
        $fields = array();
        foreach($this->_values as $k => $v)
        {           
            $i++;
            $fields[] = DB::quoteIdentifier($k).'=:val'.$i;
            $this->_vars['val'.$i] = $v;           
        }
        
        $sql .= join(',', $fields).' ';
        
        $sql .= $this->_buildWhere();

        $q = Db::query($sql, $config)->execute($this->_vars);
        
        return is_object($q) ? $q->count() : 0;
    }        
}