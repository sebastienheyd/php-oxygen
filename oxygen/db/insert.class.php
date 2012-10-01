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

class f_db_Insert
{
    private $_table;
    private $_values;
    private $_config;
   
    /**
     * Get instance of f_db_Insert
     * 
     * @param string $table     Table name
     * @param array $values     Associative array of values to insert
     * @param string $config    [optional] Config to use from the current config file. Default is "db1"
     * @return f_db_Insert 
     */
    public static function getInstance($table , array $values, $config = 'db1')
    {
        return new self($table, $values, $config);
    }

    /**
     * Main constructor
     * 
     * @param string $table     Table name
     * @param array $values     Associative array of values to insert
     * @param string $config    [optional] Config to use from the current config file. Default is "db1"
     */
    private function __construct($table, array $values, $config = 'db1')
    {
        $this->_table = $table;
        $this->_values = $values;
        $this->_config = $config;
    }   
        
    /**
     * Execute the insert into the given table
     * 
     * @return string      Return the inserted ID
     */
    public function execute()
    {
        $sql = 'INSERT INTO '.DB::quoteTable($this->_table, $this->_config).' ';
        
        $cols = array();
        $vals = array();
        
        foreach($this->_values as $k => $v)
        {
            is_string($k) ? $cols[] = $k : $k = 'var_'.$k;
            $vals[':'.$k] = $v;
        }
        
        if(!empty($cols)) $sql .= '('.join(',', array_map(array('DB', 'quoteIdentifier'), $cols)).') ';   
        
        $sql .= 'VALUES ('.join(', ', array_keys($vals)).')';
        
        Db::query($sql, $this->_config)->execute($vals);
        
        return Db::getInstance($this->_config)->getLastId();
    }    
}