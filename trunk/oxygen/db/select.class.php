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

class f_db_Select extends f_db_Where
{
    private $_cols = array();
    private $_distinct = false;
    private $_from = array();    
    private $_order = array();    
    private $_group = array();
    private $_join;
    private $_limit;

    /**
     * @return f_db_Select      Return an new instance of f_db_Select
     */
    public static function getInstance()
    {
        return new self();
    }    

    /**
     * Begin a select query
     *
     * @param array $cols       [optional] Array of columns to select. Default is an empty array 
     * @return f_db_Select      Current instance of f_db_Select
     */
    public function select(array $cols = array())
    {
        $this->_cols = array_merge($this->_cols, $cols); 
        return $this;
    }

    /**
     * Add distinct to select query
     * 
     * @param boolean $val      [optional] Add distinct to select query. Default is true
     * @return f_db_Select      Current instance of f_db_Select
     */
    public function distinct($val = true)
    {
        $this->_distinct = is_bool($val) ? $val : true;
        return $this;
    }

    /**
     * Define table to get data from
     *
     * @param string $table     The table name
     * @return f_db_Select      Current instance of f_db_Select
     */
    public function from($table)
    {
        $tables = func_get_args();
        $this->_from = array_merge($this->_from, $tables);
        return $this;
    }
    
    /**
     * Add a where condition
     *
     * @param string $key       Column to filter on
     * @param mixed $value      [optional] Value to search, set it to null if values are directly setted in $key. Default is null
     * @param string $type      [optional] Type of request AND or OR. Default is 'AND'
     * @return f_db_Select      Current instance of f_db_Select
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
     * @return f_db_Select      Current instance of f_db_Select
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
     * @return f_db_Select      Current instance of f_db_Select
     */
    public function whereNotIn($field, $values, $type = 'AND')
    {
        return parent::whereNotIn($field, $values, $type);
    } 
    
    /**
     * Add a having condition
     *
     * @param string $key       Column to filter on
     * @param mixed $value      [optional] Value to search, set it to null if values are directly setted in $key. Default is null
     * @param string $type      [optional] Type of request AND or OR. Default is 'AND'
     * @return f_db_Select      Current instance of f_db_Select
     */    
    public function having($key, $value = null, $type = 'AND')
    { 
        return parent::having($key, $value, $type);
    }    
    
    /**
     * Add a table to join
     *
     * @param string $table         The table name to join
     * @param string $condition     Joining conditions
     * @param string $type          [optional] The join type. Default is natural join.
     * @return f_db_Select          Current instance of f_db_Select
     */
    public function join($table, $condition, $type = '')
    {
        $type = strtoupper(trim($type));
        if(!in_array($type,array('LEFT', 'RIGHT', 'OUTER', 'INNER', 'LEFT OUTER', 'RIGHT OUTER'))) $type = '' ;
        
        if(preg_match('/USING\s(.*)/i', $condition, $matches))
        {
            $condition = 'USING '.Db::quoteIdentifier($matches[1]);
        }
        else
        {
            if(preg_match('/([\w\.]+)([\W\s]+)(.+)/', $condition, $matches))
            {
                $conds = array_map('trim', $matches);
                $conds = array_map(array('Db', 'quoteIdentifier'), $conds);
                unset($conds[0]);
                $condition = 'ON '.join('', $conds);
            }            
        }                

        $this->_join[] = array('table' => $table, 'condition' => $condition, 'type' => $type);
        return $this;
    }   
    
    /**
     * Add a group by condition
     * 
     * @param string $keys  Field(s) or key(s) to group by (multiple method args for multiple keys)
     * @return f_db_Select 
     */
    public function groupBy($keys)
    {
        $args = func_get_args();
        $this->_group = array_merge($this->_group, $args);
        return $this;
    }    
    
    /**
     * Add an order condition
     * 
     * @param string $key   Key or field to order request by
     * @param string $type  [optional] Type of order (ASC or DESC). Default is 'ASC'
     * @return f_db_Select 
     */
    public function orderBy($key, $type = 'ASC')
    {
        if(preg_match('/\((.*)\)/i', $key))
        {
            $this->_order[] = array('field' => $key, 'type' => '');
        }
        else
        {
            if(!in_array(strtoupper($type), array('ASC', 'DESC'))) trigger_error('SQL Select order accepts only ASC or DESC', E_USER_ERROR);
            $this->_order[] = array('field' => $key, 'type' => $type);            
        }
        
        return $this;
    }
    
    /**
     * Limit select results
     * 
     * @param integer $nbResult     Number of results to get
     * @param integer $offset       [optional] Offset of results, default is 0
     * @return f_db_Select 
     */
    public function limit($nbResult, $offset = 0)
    {
        $this->_limit = "$offset, $nbResult";
        return $this;
    }    

    /**
     * Build the select request to execute
     *
     * @return string       SQL request
     */
    public function build($config = 'db1')
    {        
        $sql  = $this->_distinct ? 'SELECT DISTINCT ' : 'SELECT ';

        $sql .= !empty ($this->_cols) ? join(', ', array_unique(array_map(array('DB', 'quoteIdentifier'), $this->_cols))).' ' : '* ';

        $sql .= 'FROM ';

        $sql .= !empty ($this->_from) ? join(', ', array_unique(array_map(array('DB', 'quoteTable'), $this->_from, array($config)))).' ' : '* ';

        if(!empty($this->_join))
        {
            foreach($this->_join as $join)
            {
                if($join['type'] != '') $sql .= $join['type'].' ';
                $sql .= 'JOIN '.Db::quoteTable($join['table'], $config).' '.Db::quoteIdentifier($join['condition']).' ';
            }
        }

        $sql .= $this->_buildWhere();
        
        if(!empty($this->_group))
        {
            $sql .= 'GROUP BY ';            
            $sql .= join(', ', array_map(array('DB', 'quoteIdentifier'), $this->_group)).' ';           
        }
        
        $sql .= $this->_buildHaving();
        
        $c = count($this->_order);

        if($c > 0)
        {
            $sql .= 'ORDER BY ';
            
            foreach($this->_order as $k => $v)
            {
                $sql .= DB::quoteIdentifier($v['field']).' '.$v['type'].' ';
                if($c > 1 && $k+1 < $c) $sql .= ', ';
            }
        }
                
        if(isset($this->_limit)) $sql .= ' LIMIT '.$this->_limit;    

        return trim($sql);
    }        

    /**
     * Execute the builded query
     *
     * @param string $config    [optional] Config to use from the current config file. Default is "db1"
     * @return DB               Return instance of DB
     */
    public function execute($config = 'db1')
    {
        return DB::query($this->build($config), $config)->execute($this->_vars);
    }
    
    /**
     * Fetch first row with the given fetch style
     *
     * @param integer $fetchStyle   [optional] Fetching type, use PDO static vars. Default is PDO::FETCH_ASSOC
     * @param string $args          [optional] Additionnal args used by some fetch style. Default is null
     * @param string $config        [optional] Config to use from the current config file. Default is "db1"
     * @return array|false          Return the first row results as an array or false if no result
     */
    public function fetch($fetchStyle = PDO::FETCH_ASSOC, $args = null, $config = 'db1')
    {        
        return $this->execute($config)->fetch($fetchStyle, $args);
    }
    
    /**
     * Alias of fetch(PDO::FETCH_COLUMN)
     * 
     * @param string $config        [optional] Config to use from the current config file. Default is "db1"
     * @return string|false          Return the first row results as an array or false if no result
     */
    public function fetchCol($config = 'db1')
    {
        return $this->fetch(PDO::FETCH_COLUMN, null, $config);
    }
    
    /**
     * Fetch all (multiple) results with the given fetch style
     *
     * @param integer $fetchStyle   [optional] Fetching type, use PDO static vars. Default is PDO::FETCH_ASSOC
     * @param string $args          [optional] Additionnal args used by some fetch style. Default is null
     * @param string $config        [optional] Config to use from the current config file. Default is "db1" 
     * @return array                Return an array of results or an empty array if none
     */    
    public function fetchAll($fetchStyle = PDO::FETCH_ASSOC, $args = null, $config = 'db1')
    {
        return $this->execute($config)->fetchAll($fetchStyle, $args);
    }
    
    /**
     * Fetch first row in an object with the given class name
     * 
     * @param string $className         [optional] The class to fetch result into. Default is 'stdClass'
     * @param string $config            [optional] Config to use from the current config file. Default is "db1"
     * @return stdClass|object|false    Return an object containing the current query results
     */    
    public function fetchObject($className = 'stdClass', $config = 'db1')
    {
        return $this->execute($config)->fetchObject($className);
    }
    
    /**
     * Alias of fetchAll(PDO::FETCH_COLUMN, $colNum). Will fetch result by a column number
     * 
     * @param integer $colNum   [optional] The column number to fetch. Default is null (0)
     * @param string $config    [optional] Config to use from the current config file. Default is "db1"
     * @return mixed            Return the column value 
     */
    public function fetchAllColumn($colNum = null, $config = 'db1')
    {
        return $this->execute($config)->fetchAll(PDO::FETCH_COLUMN, $colNum);
    }    
    
    /**
     * Alias of fetchAll(PDO::FETCH_CLASS). Will fetch result into an array of objects.
     * 
     * @param strinf $className         [optional] The class name to instantiate with fetched values. Default is 'stdClass'
     * @param type $preloadConstructor  [optional] Must we preload the constructor before insert fetched data into the class. Default is false.
     * @param string $config            [optional] Config to use from the current config file. Default is "db1"
     * @return array                    Return an array of objects of the given class name
     */    
    public function fetchAllObject($className = 'stdClass', $preloadConstructor = true, $config = 'db1')
    {
        return $this->execute($config)->fetchAllObject($className, $preloadConstructor);
    }
    
    /**
     * Count current query result(s)
     * 
     * @param string $config     [optional] Config to use from the current config file. Default is "db1"
     * @return integer           The number of rows (results)
     */    
    public function count($config = 'db1')
    {
        return $this->execute($config)->count();
    }

}