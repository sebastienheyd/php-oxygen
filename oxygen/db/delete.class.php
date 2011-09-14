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

class f_db_Delete
{
    private $_cols = array();
    private $_distinct = false;
    private $_from = array();
    private $_where = array();
    private $_order = array();
    private $_group = array();
    private $_vars = array();
    private $_join;
    private $_executed = false;
    private $_limit;

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
     * @return f_db_Delete      Return current instance of f_db_Delete
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
     * Checks if given string has an operator into
     *
     * @param string $str   String to get operator from
     * @return boolean      true if string has an operator
     */
    private function _hasOperator($str)
	{
		return preg_match("/(\s|<|>|!|=|is null|is not null|like)/i", trim($str)) > 0;
	}       

    /**
     * Build the select request to execute
     *
     * @param string $config    [optional] Config to use from the current config file. Default is "default"
     * @return integer          Return the number of affected rows
     */
    public function execute($config = 'default')
    {        
        $sql  = 'DELETE FROM '.$this->_from.' ';     

        $c = count($this->_where);
        if($c > 0)
        {
            $sql .= 'WHERE ';

            foreach($this->_where as $k => $cond)
            {
                $sql .= $cond['cond'].$cond['var'].' ';

                if($c > 1 && $c-1 != $k) $sql .= $cond['type'].' ';
            }
        }    
        
        $sql = trim($sql);

        $q = DB::query($sql, $config)->execute($this->_vars);
        
        return is_object($q) ? $q->count() : 0;
    }       
}