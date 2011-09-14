<?php
class f_test_Database extends f_test_Abstract
{   
    /**
     * Check connexion
     */
    public function test_connexion()
    {
        $this->label = 'Connexion to the configured default database';
        return DB::getInstance('default', false) ? true : $this->halt();
    }

    public function test_create()
    {        
        $this->label = 'Adding mockup datas into database';
        
        
//        $db = DB::createTable('test')   ->autoId('id')
//                                        ->date('date')
//                                        ->date('test')
//                                        ->time('time')
//                                        ->dateTime('datetime')
//                                        ->varchar('title')
//                                        ->varchar('url', 40)
//                                        ->text('intro')
//                                        ->enum('status', array('draft', 'live'))
//                                        ->addUnique('id')
//                                        ->addUnique('url')
//                                        ->addIndex('title')
//                                        ->comment('Base de test')
//                                        ->execute();
        
        //var_dump($db);
        //$q = DB::query(file_get_contents(dirname(__FILE__).DS.'files'.DS.'mockup.sql'))->execute();
        

        //$db->execute();
    }
}
