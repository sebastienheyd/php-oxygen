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

interface f_cache_Interface
{
    /**
	 * Fetch datas from cache
	 *
	 * @param 	mixed           Unique cache identifier
	 * @return 	mixed|false		Datas or false if none
	 */
    public function get($id);
    
	/**
	 * Save content into cache
	 *
	 * @param 	string		Unique cache identifier
	 * @param 	mixed		Datas to store
	 * @param 	int			Cache lifetime in seconds
     * 
     * @return boolean  Return true on success
     */
    public function save($id, $datas, $ttl = 60);
    
	/**
	 * Delete from Cache
	 *
	 * @param 	string		Unique cache identifier
	 * @return 	boolean		Return true on success
	 */
    public function delete($id);
    
    /**
     * Flush all cache
     * 
     * @return boolean  Return true on success 
     */
    public function flush(); 
    
    /**
     * Check if handler is supported
     * 
     * @return boolean  Return true if handler is supported 
     */
    public function isSupported();   
}