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

class f_session_Database implements f_session_Interface
{
    protected $_savePath;
    protected $_sessionName;
    private $_tableName;
    private $_lifeTime;
    private $_db;
    
    public function __construct($lifetime)
    {        
        // check if session data must be stored in database
        $config = Config::get('session');
       
        $this->_db = DB::getInstance(isset($config->db_config) ? $config->db_config : 'db1');
        $this->_tableName = Db::prefixTable(isset($config->table) ? $config->table : 'sessions');

        // create table if necessary
        if(!$this->_db->tableExists($this->_tableName, $config->db_config)) $this->_initSessionTable();

        $this->_lifeTime = $lifetime;
    }

    public function open($savePath, $sessionName)
    {
        $this->_savePath = $savePath;
        $this->_sessionName = $sessionName;
        return true;
    }

    public function close()
    {
        return true;
    }

    public function read($id)
    {
        $sql = 'SELECT `session_data` FROM `'.$this->_tableName.'` WHERE `session_id` = ? AND `expires` > ?';
        return $this->_db->prepare($sql)->execute($id, time())->fetchCol();
    }

    public function write($id, $data)
    {
        $sql = 'REPLACE `'.$this->_tableName.'` (`session_id`,`session_data`,`expires`) VALUES(?, ?, ?)';
        $this->_db->prepare($sql)->execute($id, $data, time() + $this->_lifeTime);
        return true;
    }

    public function destroy($id)
    {
        $res = $this->_db->prepare('DELETE FROM `'.$this->_tableName.'` WHERE `session_id`=?')->execute($id);
        return true;
    }

    public function gc($lifetime)
    {
        $this->_db->queryExec('DELETE FROM `'.$this->_tableName.'` WHERE `expires` < UNIX_TIMESTAMP();');
        return true;
    }
    
    private function _initSessionTable()
    {
        $sql = 'CREATE TABLE `'.$this->_tableName.'` (
           `session_id` varchar(100) NOT NULL default "",
           `session_data` text NOT NULL,
           `expires` int(11) NOT NULL default "0",
            PRIMARY KEY  (`session_id`)
            ) ENGINE = MyIsam DEFAULT CHARSET=utf8 COLLATE utf8_unicode_ci;';

        return $this->_db->queryExec($sql) == 1;
    }    
}