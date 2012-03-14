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

class f_generator_Model
{
    private $_config;
    private $_tableName;
    private $_className;
    private $_primaryKeys = array();
    private $_fields;
    private $_prefix;

    /**
     * Main constructor
     * 
     * @param string $tableName     The table name to use for class generation
     * @param string $prefix        Table prefix, will be removed from table name
     * @param string $config        [optional] Database configuration to use from the current config file.
     */
    private function __construct($tableName, $prefix, $config)
    {
        $tableName = preg_replace('/^'.$prefix.'/i', '', $tableName);
        $this->_tableName = $prefix.$tableName;
        $this->_config = $config;
        $this->_className = $this->_getClassName($tableName);
        $this->_prefix = $prefix;

        $this->_getDescription();
        $this->_getForeignKeys();
    }

    /**
     * Get an instance of f_generator_Model
     * 
     * @param type $tableName       The table name to use for class generation
     * @param string $prefix        [optional] Prefix of table name. Default is ""
     * @param string $config        [optional] Config to use from the current config file. Default is "db1"
     * @return f_generator_Model    Instance of f_generator_Model
     */
    public static function fromTable($tableName, $prefix = '', $config = 'db1')
    {
        return new self($tableName, $prefix, $config);
    }

    /**
     * Get current table description and sets values in _fields variable
     */
    private function _getDescription()
    {
        $fields = DB::query('DESCRIBE `'.$this->_tableName.'`', $this->_config)->fetchAll();

        foreach($fields as $field)
        {
            $this->_fields[$field['Field']] = $field;
            if($field['Key'] == 'PRI') $this->_primaryKeys[] = $field['Field'];
        }
    }

    /**
     * Get current table setted foreign keys and sets values in _fields variable
     */
    private function _getForeignKeys()
    {
        $this->_getReferenceKeys();
        $this->_getReferencedByKeys();
    }

    /**
     * Get fields that reference the current table and put them in _fields variable
     */    
    private function _getReferenceKeys()
    {
        $sql = <<<SQL
        SELECT u.column_name, u.referenced_table_name, u.referenced_column_name FROM information_schema.table_constraints AS c
        INNER JOIN information_schema.key_column_usage AS u
        USING ( constraint_schema, constraint_name )
        WHERE c.constraint_type = 'FOREIGN KEY'
        AND c.table_schema = ?
        AND c.table_name = ?
SQL;
        
        $referenceKeys = DB::query($sql, $this->_config)->execute(Config::get($this->_config, 'base'), $this->_tableName)->fetchAll();

        if(!empty($referenceKeys))
        {
             foreach($referenceKeys as $refKey)
             {
                 $result = array(   'table' => $refKey['referenced_table_name'], 
                                    'primary' => $refKey['referenced_column_name'],
                                    'class'     => $this->_getClassName($refKey['referenced_table_name'])
                                );
                 
                 $this->_fields[$refKey['column_name']]['Get'] = $result;
             }
        }        
    }

    /**
     * Get fields that are referenced by the current table and put them in _fields variable
     */     
    private function _getReferencedByKeys()
    {
        $sql = <<<SQL
        SELECT u.table_name, u.column_name, u.referenced_column_name FROM information_schema.table_constraints AS c
        INNER JOIN information_schema.key_column_usage AS u
        USING ( constraint_schema, constraint_name )
        WHERE c.constraint_type = 'FOREIGN KEY'
        AND c.table_schema = ?
        AND u.referenced_table_name = ?
SQL;

        $referencedKeys = DB::query($sql, $this->_config)->execute(array(Config::get($this->_config, 'base'), $this->_tableName))->fetchAll();

        if(!empty($referencedKeys))
        {
             foreach($referencedKeys as $refKey)
             {                
                 $result = array(   'table'         => $refKey['table_name'], 
                                    'column'        => $refKey['column_name'], 
                                    'method'        => 'getAll'.$this->_getMethodSuffix($refKey['table_name']),
                                    'searchMethod'  => 'load'.$this->_getMethodSuffix($refKey['table_name']),
                                    'class'         => $this->_getClassName($refKey['table_name'])
                                );
                 
                 $this->_fields[$refKey['referenced_column_name']]['ReferencedBy'][] = $result;
             }
        }
    }
    
    /**
     * Return the getAll and load methods suffixes
     * 
     * @param string $tableName     The table name as a string
     * @return string               The camelized suffix to use
     */
    private function _getMethodSuffix($tableName)
    {
        $tableName = str_replace($this->_prefix, '', $tableName);
        return String::camelize($tableName);        
    }
    
    /**
     * Return the class name of the class to generate from the table name
     * 
     * @param string $tableName     The input table name
     * @return string               The class name
     */
    private function _getClassName($tableName)
    {
        $tableName = str_replace($this->_prefix, '', $tableName);
        $className = String::camelize($tableName);
        return preg_replace('/s$/', '', $className);
    }
    
    /**
     * Write the model class file
     * 
     * @return boolean      Return true when success
     */
    public function toModel()
    {
        $tpl = Template::getInstance(FW_DIR.DS.'generator'.DS.'templates'.DS.'class.tpl');
        $tpl->assign('tableName', $this->_tableName);
        $tpl->assign('className', $this->_className);
        $tpl->assign('primary', join("','", $this->_primaryKeys));
        $tpl->assign('vars', $this->_fields);
        $tpl->assign('fields', array_map(array($this, '_parseVars'), $this->_fields));       
        
        file_put_contents(PROJECT_DIR.DS.'model'.DS.strtolower($this->_className).'.class.php', $tpl->get());

        return true;
    }
    
    /**
     * Parse table properties to build class methods
     * 
     * @param string $field     The field name from the current table
     * @return array            An array of values to build the method
     */
    private function _parseVars($field)
    {
        $field['getMethodName'] = 'get'.String::camelize($field['Field']);
        $field['setMethodName'] = 'set'.String::camelize($field['Field']);
        
        if($field['Key'] != '') 
        {
            $field['LoadByMethodName'] = 'loadBy'.String::camelize($field['Field']);
            
            $field['LoadMethod'] = 'fetchAllObject';
            if($field['Key'] == 'UNI' || $field['Key'] == 'PRI' && count($this->_primaryKeys) == 1)
            {
                $field['LoadMethod'] = 'fetchObject';
            }
        }                   
        
        return $field;
    }

    /**
     * Return the SQL query to use for the table creation
     * 
     * @return string       The SQL query 
     */
    public function toSql()
    {
        $fields = DB::query('SHOW CREATE TABLE `'.$this->_tableName.'`', $this->_config)->fetchAll();
        return $fields[0]['Create Table'];
    }
}