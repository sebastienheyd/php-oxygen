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

abstract class f_db_Where
{
    protected $_where = array();
    protected $_having = array();
    protected $_vars = array();
    
    /**
     * Add a where condition
     *
     * @param string $key       Column to filter on
     * @param mixed $value      [optional] Value to search, set it to null if values are directly setted in $key. Default is null
     * @param string $type      [optional] Type of request AND or OR. Default is 'AND'
     * @return f_db_Where       Current instance of f_db_Where
     */
    protected function where($key, $value = null, $type = 'AND')
    {
        $cond = array();

        if (!is_array($key)) $key = array($key => $value);

        foreach($key as $k => $v)
        {
            $varName = 'var'.count($this->_where);
            
            // case of no escape
            if(preg_match('/\((.*)\)/i', $k))
            {
                if($value !== null)
                {
                    $k = str_replace('?', ':'.$varName, $k);
                    $this->_vars[$varName] = $v;
                }
                
                $this->_where[] = array('noescape' => $k, 'type' => $type);
                
                return $this;                    
            }

            if($v === null)
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
     * Add a IN condition
     * 
     * @param string $field     Field name to filter
     * @param array $values     Array or comma separated list of values to check if present in field
     * @param string $type      [optional] Type of request AND or OR. Default is 'AND'
     * @return f_db_Where       Current instance of f_db_Where
     */
    protected function whereIn($field, $values, $type = 'AND')
    {
        if(!is_array($values)) $values = explode(',', $values);
        $values = join(',', array_map(array('Db', 'escape'), $values));
        $this->_where[] = array('noescape' => Db::quoteIdentifier($field).' IN ('.$values.')', 'type' => $type);
        return $this;
    }
    
    /**
     * Add a NOT IN condition
     * 
     * @param string $field     Field name to filter
     * @param array $values     Array or comma separated list of values to check if present in field
     * @param string $type      [optional] Type of request AND or OR. Default is 'AND'
     * @return f_db_Where       Current instance of f_db_Where
     */
    protected function whereNotIn($field, $values, $type = 'AND')
    {
        if(is_array($values)) $values = join(',', array_map(array('Db', 'escape'), $values));
        $this->_where[] = array('noescape' => Db::quoteIdentifier($field).' NOT IN ('.$values.')', 'type' => $type);
        return $this;
    }
    
    /**
     * Add a having condition
     *
     * @param string $key       Column to filter on
     * @param mixed $value      [optional] Value to search, set it to null if values are directly setted in $key. Default is null
     * @param string $type      [optional] Type of request AND or OR. Default is 'AND'
     * @return f_db_Where       Current instance of f_db_Where
     */    
    protected function having($key, $value = null, $type = 'AND')
    {        
        $cond = array();

        if (!is_array($key)) $key = array($key => $value);

        foreach($key as $k => $v)
        {
            $varName = 'hvar'.count($this->_having);
            
            // case of no escape
            if(preg_match('/\((.*)\)/i', $k))
            {
                if($value !== null)
                {
                    $k = str_replace('?', ':'.$varName, $k);
                    $this->_vars[$varName] = $v;
                }
                
                $this->_having[] = array('noescape' => $k, 'type' => $type);
                
                return $this;                    
            }

            if($v === null)
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

            $this->_having[] = $cond ;
        }

        return $this;
    }       
    
/**
     * Build the HAVING sql condition
     * 
     * @return string 
     */    
    protected function _buildHaving()
    {
        $sql = '';
                
        if(!empty($this->_having))
        {
            $sql .= 'HAVING ';

            foreach($this->_having as $k => $cond)
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
                
                if(isset($this->_having[$k+1]['type'])) $sql .= $this->_having[$k+1]['type'].' ';                
            }
        } 
        
        return $sql;
    }        
    
    /**
     * Build the WHERE sql condition
     * 
     * @return string 
     */
    protected function _buildWhere()
    {
        $sql = '';
        
        if(!empty($this->_where))
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
         
                if(isset($this->_where[$k+1]['type'])) $sql .= $this->_where[$k+1]['type'].' ';                   
            }
        }

        return $sql;
    }
    
    /**
     * Execute the builded query
     *
     * @param string $config    [optional] Config to use from the current config file. Default is "db1"
     * @return DB               Return instance of DB
     */    
    abstract public function execute($config = 'db1');
}