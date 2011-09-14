<?php
class f_test_Session extends f_test_Abstract
{
    private $_session;

    public function  __construct() 
    {
        $label = 'Testing session management';
        if(Config::getInstance()->session->type == 'database')
        {
            $label .= ' using database (modify your config file to use file system)';
        }
        else
        {
            $label .= ' using file system (modify your config file to use database system)';
        }    
        $this->testLabel = $label;
        $this->_session = Session::getInstance();
    }

    /**
     * insert values in session
     *
     * @return boolean
     */
    public function test_set()
    {
        $this->label = 'Inserting values in session in different ways and verify their presence';
        
        // set session value method 1
        $this->_session->test1 = 'test1 value';
        $this->_session->test3 = array('1' => 'ok');

        // set session value method 2
        $this->_session->setTest2('test2 value');

        $object = new stdClass();
        $object->value = 'ok';
        $this->_session->setTest4($object);

        return (isset($_SESSION['test1']) && isset($_SESSION['test2']) && isset($_SESSION['test3']) && isset($_SESSION['test4']));
    }

    /**
     * get values from session
     *
     * @return boolean
     */
    public function test_get()
    {
        $this->label = 'Getting values from session in different ways and verify their correctness';
        
        return ($this->_session->test1 == 'test1 value' && 
                $this->_session->getTest2() == 'test2 value' && 
                count($this->_session->test3) == 1 && 
                $this->_session->getTest4() instanceof stdClass);
    }

    /**
     * unset values in session
     *
     * @return boolean
     */
    public function test_unset()
    {
        $this->label = 'Unset values from session in diffent ways and verify if their no longer present into it';
        
        // unset method 1
        $this->_session->test1 = null;
        $this->_session->test3 = null;

        // unset method 2
        $this->_session->unsetTest2();
        $this->_session->unsetTest4();

        return (!isset($_SESSION['test1']) && !isset($_SESSION['test2']) && !isset($_SESSION['test3']) && !isset($this->_session->test4));
    }

    /**
     * unset current session values
     * @return boolean
     */
    public function test_unset_session()
    {
        $this->label = 'Clean session and verify she is empty';
        
        $this->_session->test3 = 'test3 value';
        $this->_session->clean();
        return (empty($_SESSION));
    }

    /**
     * test session destroy
     * @return boolean
     */
    public function test_destroy()
    {
        $this->label = 'Destroy session and verify that id has changed and values are all deleted';
        
        $this->_session->test = 'value 1';
        $currentId = $this->_session->getId();
        $this->_session->destroy();
        $this->_session->test = 'value 2';
        return ($currentId != $this->_session->getId() && $this->_session->getId() != '' && $_SESSION['test'] == 'value 2' && $this->_session->test == 'value 2');
    }
}
