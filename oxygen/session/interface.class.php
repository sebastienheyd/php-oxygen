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

interface f_session_Interface
{
    /**
     * Executed when the session is being started.
     * 
     * @param string $savePath
     * @param string $sessionName
     * 
     * @return boolean
     */
    public function open($savePath, $sessionName);
    
    /**
     * Executed after the session write callback has been called.
     * 
     * @return boolean
     */
    public function close();
    
    /**
     * Read stored data
     * 
     * @param string $id          Current session id
     * 
     * @return array              A unserialized version the $_SESSION superglobal
     */
    public function read($id);
    
    /**
     * Called when the session needs to be saved and closed
     * 
     * @param string $id          Current session id
     * @param string $data        Data to store, a serialized version the $_SESSION superglobal
     */
    public function write($id, $data);
    
    /**
     * Executed when a session is destroyed with session_destroy() 
     * or with session_regenerate_id() with the destroy parameter set to TRUE
     * 
     * @param string $id          Current session id
     */
    public function destroy($id);
    
    /**
     * The garbage collector callback is invoked internally by 
     * PHP periodically in order to purge old session data.<br />
     * The value of lifetime which is passed to this callback 
     * can be set in session.gc_maxlifetime.
     * 
     * @param int $maxlifetime    Max session lifetime
     */
    public function gc($maxlifetime);
}