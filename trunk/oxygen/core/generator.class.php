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

class Generator
{
    /**
     * Generate classes files for the given table name
     * 
     * @param string $tableName     Input table name
     * @param string $prefix        [optional] Table prefix, will be remove to model name
     * @param string $config        [optional] Database configuration to use from the current config file. Default is "default"
     * @return boolean              Return true if generation is successful
     */
    public static function model($tableName, $prefix = '', $config='default')
    {
        return f_generator_Model::fromTable($tableName, $prefix, $config)->toModel();
    }
    
    /**
     * Generate all classes files for all tables
     * 
     * @param string $prefix        [optional] Only generate for table with this prefix. Default is ''
     * @param string $config        [optional] Database configuration to use from the current config file. Default is "default"
     * @return boolean              Return number of successful generations
     */
    public static function models($prefix = '', $config = 'default')
    {
        $tables = DB::getTablesList($prefix, $config);
        
        if(!empty($tables))
        {
            foreach ($tables as $table)
            {
                Generator::model($table, $prefix, $config);
            }    
        }
        
        return count($tables);
    }
}