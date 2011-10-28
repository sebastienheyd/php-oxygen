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

class f_db_Select
{
    private $_cols = array();
    private $_distinct = false;
    private $_from = array();
    private $_where = array();
    private $_order = array();
    private $_having = array();
    private $_group = array();
    private $_vars = array();
    private $_join;
    private $_executed = false;
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
     * Define table to get datas from
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

        $this->_join[] = array('table' => Db::quoteTable($table), 'condition' => $condition, 'type' => $type);
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
                if($this->_hasOperator($k))
                {
                    list($n, $o) = preg_split('/\s/i', trim($k), 0);
                    $cond['cond'] = Db::quoteIdentifier($n).$o.' ';
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
     * Add a having condition
     *
     * @param string $key       Column to filter on
     * @param mixed $value      [optional] Value to search, set it to null if values are directly setted in $key. Default is null
     * @param string $type      [optional] Type of request AND or OR. Default is 'AND'
     * @return f_db_Select      Current instance of f_db_Select
     */    
    public function having($key, $value = null, $type = 'AND')
    {
        $cond = array();

        if (!is_array($key))
		{
			$key = array($key => $value);
		}

        foreach($key as $k => $v)
        {
            $varName = 'hvar'.count($this->_having);
            
            // case of no escape
            if(preg_match('/\((.*)\)/i', $k))
            {
                if(!is_null($value))
                {
                    $k = str_replace('?', ':'.$varName, $k);
                    $this->_vars[$varName] = $v;
                }
                
                $this->_having[] = array('noescape' => $k);
                
                return $this;                    
            }

            if(is_null($v))
            {
                $cond['cond'] = DB::quoteIdentifier($k).' IS NULL ';
                $cond['var'] = '';
            }
            else
            {
                if($this->_hasOperator($k))
                {
                    list($n, $o) = preg_split('/\s/i', trim($k), 0);
                    $cond['cond'] = Db::quoteIdentifier($n).$o.' ';
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
     * Add a IN condition
     * 
     * @param string $field     Field name to filter
     * @param array $values     Array of values to check if present in field
     * @param string $type      [optional] Type of request AND or OR. Default is 'AND'
     * @return f_db_Select      Current instance of f_db_Select
     */
    public function whereIn($field, array $values, $type = 'AND')
    {
        $values = join(',', array_map(array('Db', 'escape'), $values));
        $this->_where[] = array('noescape' => Db::quoteIdentifier($field).' IN ('.$values.')', 'type' => $type);
        return $this;
    }
    
    /**
     * Add a NOT IN condition
     * 
     * @param string $field     Field name to filter
     * @param array $values     Array of values to check if present in field
     * @param string $type      [optional] Type of request AND or OR. Default is 'AND'
     * @return f_db_Select      Current instance of f_db_Select
     */
    public function whereNotIn($field, array $values, $type = 'AND')
    {
        $values = join(',', array_map(array('Db', 'escape'), $values));
        $this->_where[] = array('noescape' => Db::quoteIdentifier($field).' NOT IN ('.$values.')', 'type' => $type);
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
        if(preg_match('/\((.*)\)|([^a-zA-Z0-9\.`\'\\s"])/i', $key))
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
     * Checks if given string has an operator into
     *
     * @param string $str   Request string
     * @return boolean      true if string has an operator
     */
    private function _hasOperator($str)
	{
		return preg_match("/(\s|<|>|!|=|is null|is not null|like)/i", trim($str)) > 0;
	}       

    /**
     * Build the select request to execute
     *
     * @return string       SQL request
     */
    public function build()
    {        
        $sql  = $this->_distinct ? 'SELECT DISTINCT ' : 'SELECT ';

        $sql .= !empty ($this->_cols) ? join(', ', array_unique(array_map(array('DB', 'quoteIdentifier'), $this->_cols))).' ' : '* ';

        $sql .= 'FROM ';

        $sql .= !empty ($this->_from) ? join(', ', array_unique(array_map(array('DB', 'quoteTable'), $this->_from))).' ' : '* ';

        if(!empty($this->_join))
        {
            foreach($this->_join as $join)
            {
                if($join['type'] != '') $sql .= $join['type'].' ';
                $sql .= 'JOIN '.$join['table'].' '.$join['condition'].' ';
            }
        }

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
                
                if($c > 1 && $k+1 < $c) $sql .= $cond['type'].' ';                   
            }
        }            
        
        if(!empty($this->_group))
        {
            $sql .= 'GROUP BY ';            
            $sql .= join(', ', array_map(array('DB', 'quoteIdentifier'), $this->_group));           
        }
        
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
        
        $c = count($this->_having);
        if($c > 0)
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
                
                if($c > 1 && $k+1 < $c) $sql .= $cond['type'].' ';                    
            }
        }         
        
        if(isset($this->_limit))
        {
            $sql .= ' LIMIT '.$this->_limit;
        }        

        return trim($sql);
    }

    /**
     * Execute the builded query
     *
     * @param string $config    [optional] Config to use from the current config file. Default is "default"
     * @return DB               Return instance of DB
     */
    public function execute($config = 'default')
    {
        $this->_executed = true;
        return DB::query($this->build(), $config)->execute($this->_vars);
    }
    
    /**
     * Fetch first row with the given fetch style
     *
     * @param integer $fetchStyle   [optional] Fetching type, use PDO static vars. Default is PDO::FETCH_ASSOC
     * @param string $args          [optional] Additionnal args used by some fetch style. Default is null
     * @param string $config        [optional] Config to use from the current config file. Default is "default"
     * @return array                Return the first row results as an array
     */
    public function fetch($fetchStyle = PDO::FETCH_ASSOC, $args = null, $config = 'default')
    {        
        return $this->execute($config)->fetch($fetchStyle, $args);
    }
    
    /**
     * Fetch all (multiple) results with the given fetch style
     *
     * @param integer $fetchStyle   [optional] Fetching type, use PDO static vars. Default is PDO::FETCH_ASSOC
     * @param string $args          [optional] Additionnal args used by some fetch style. Default is null
     * @param string $config        [optional] Config to use from the current config file. Default is "default" 
     * @return array                Return an array of results
     */    
    public function fetchAll($fetchStyle = PDO::FETCH_ASSOC, $args = null, $config = 'default')
    {
        return $this->execute($config)->fetchAll($fetchStyle, $args);
    }
    
    /**
     * Fetch first row in an object with the given class name
     * 
     * @param string $className         [optional] The class to fetch result into. Default is 'stdClass'
     * @param string $config            [optional] Config to use from the current config file. Default is "default"
     * @return stdClass|object|false    Return an object containing the current query results
     */    
    public function fetchObject($className = 'stdClass', $config = 'default')
    {
        return $this->execute($config)->fetchObject($className);
    }
    
    /**
     * Alias of fetchAll(PDO::FETCH_COLUMN, $colNum). Will fetch result by a column number
     * 
     * @param integer $colNum   [optional] The column number to fetch. Default is null (0)
     * @param string $config    [optional] Config to use from the current config file. Default is "default"
     * @return mixed            Return the column value 
     */
    public function fetchAllColumn($colNum = null, $config = 'default')
    {
        return $this->execute($config)->fetchAll(PDO::FETCH_COLUMN, $colNum);
    }    
    
    /**
     * Alias of fetchAll(PDO::FETCH_CLASS). Will fetch result into an array of objects.
     * 
     * @param strinf $className         [optional] The class name to instantiate with fetched values. Default is 'stdClass'
     * @param type $preloadConstructor  [optional] Must we preload the constructor before insert fetched datas into the class. Default is false.
     * @param string $config            [optional] Config to use from the current config file. Default is "default"
     * @return array                    Return an array of objects of the given class name
     */    
    public function fetchAllObject($className = 'stdClass', $preloadConstructor = true, $config = 'default')
    {
        return $this->execute($config)->fetchAllObject($className, $preloadConstructor);
    }
    
    /**
     * Count current query result(s)
     * 
     * @param string $config     [optional] Config to use from the current config file. Default is "default"
     * @return integer           The number of rows (results)
     */    
    public function count($config = 'default')
    {
        return $this->execute($config)->count();
    }

}