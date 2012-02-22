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

class f_db_Insert
{
    private $_db;
    private $_table;
    private $_values;
   
    /**
     * Get instance of f_db_Insert
     * 
     * @param string $table     Table name
     * @param array $values     Associative array of values to insert
     * @param string $config    [optional] Config to use from the current config file. Default is "default"
     * @return f_db_Insert 
     */
    public static function getInstance($table , array $values, $config = 'default')
    {
        return new self($table, $values, $config);
    }

    /**
     * Main constructor
     * 
     * @param string $table     Table name
     * @param array $values     Associative array of values to insert
     * @param string $config    [optional] Config to use from the current config file. Default is "default"
     */
    private function __construct($table, array $values, $config = 'default')
    {
        $this->_db = DB::getInstance($config);
        $this->_table = $table;
        $this->_values = $values;
    }   
        
    /**
     * Execute the insert into the given table
     * 
     * @return boolean      Return true if insert succeed
     */
    public function execute()
    {
        $sql = 'INSERT INTO '.DB::quoteTable($this->_table).' ';
        
        $cols = array();
        $vals = array();
        
        foreach($this->_values as $k => $v)
        {
            is_string($k) ? $cols[] = $k : $k = 'var_'.$k;
            $vals[':'.$k] = $v;
        }
        
        if(!empty($cols))
        {
            $sql .= '('.join(',', array_map(array('DB', 'quoteIdentifier'), $cols)).') ';
        }       
        
        $sql .= 'VALUES ('.join(', ', array_keys($vals)).')';
        
        $q = $this->_db->prepare($sql)->execute($vals);        
        
        if(is_object($q))
        {
            if($q->count() == 1) return true;
        }
        
        return false;
    }    
}