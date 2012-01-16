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

class f_db_Update
{
    private $_table;
    private $_values;
    private $_where;
    private $_vars;
   
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
     * @return f_db_Update      Return current instance of f_db_Update
     */
    public function where($key, $value = null, $type = 'AND')
    {
        $cond = array();

        if (!is_array($key))
		{
			$key = array($key => $value);
		}

        foreach($key as $k => $v)
        {
            $varName = 'var'.count($this->_where);
            
            // case of no escape
            if(preg_match('/\((.*)\)/i', $k))
            {
                if(!is_null($value))
                {
                    $k = str_replace('?', ':'.$varName, $k);
                    $this->_vars[$varName] = $v;
                }
                
                $this->_where[] = array('noescape' => $k);
                
                return $this;                    
            }

            if(is_null($v))
            {
                $cond['cond'] = DB::quoteIdentifier($k).' IS NULL ';
                $cond['var'] = '';
            }
            else
            {
                if(Db::hasOperator($k))
                {
                    list($n, $o) = preg_split('/\s/i', trim($k), 2);
                    $cond['cond'] = Db::quoteIdentifier($n).' '.$o.' ';
                }
                else
                {
                    $cond['cond'] = DB::quoteIdentifier($k).'= ';
                }

                $cond['var'] = ':'.$varName;
                $this->_vars[$varName] = $v;
            }
           
            $cond['type'] = $type;

            $this->_where[] = $cond ;
        }

        return $this;
    }
        
    /**
     * Execute the current update builded query
     * 
     * @param string $config    [optional] Config to use from the current config file. Default is "default"
     * @return integer          Return the number of affected rows
     */
    public function execute($config = 'default')
    {
        $sql = 'UPDATE '.DB::quoteTable($this->_table).' SET ';
        
        $i = 0;
        $fields = array();
        foreach($this->_values as $k => $v)
        {           
            $i++;
            $fields[] = DB::quoteIdentifier($k).'=:val'.$i;
            $this->_vars['val'.$i] = $v;           
        }
        
        $sql .= join(',', $fields).' ';
        
        $c = count($this->_where);
        if($c > 0)
        {
            $sql .= 'WHERE ';

            foreach($this->_where as $k => $cond)
            {
                if(isset($cond['noescape']))
                {
                    $sql .= $cond['noescape'].' ';
                }
                else
                {
                    $condition = '';                    
                    if(isset($cond['cond'])) $condition = $cond['cond'];
                    
                    $sql .= $condition.$cond['var'].' ';

                }
                
                if($c > 1 && $c-1 != $k) $sql .= $cond['type'].' ';                    
            }
        } 

        $q = Db::query($sql, $config)->execute($this->_vars);
        
        return is_object($q) ? $q->count() : 0;
    }             
}