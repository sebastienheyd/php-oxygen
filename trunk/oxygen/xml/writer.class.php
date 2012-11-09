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

class f_xml_Writer extends XMLWriter
{
    private $_startDocument;    
    
    /**
     * @param string $startDocument          If true, starts a new UTF-8 XML ?
     * @return f_xml_Writer
     */
    public static function getInstance($startDocument = true)
    {
        return new self($startDocument);                
    }
    
    /**
     * Main constructor 
     */
    private function __construct($startDocument)
    {
        $this->openMemory();
        $this->setIndent(true);
        $this->setIndentString('    ');
        $this->_startDocument = $startDocument;
        if($startDocument === true) $this->startDocument('1.0', 'UTF-8');
    }
        
    /**
	 * Create start element tag
     * 
	 * @param string $name          Element name to start
	 * @param array $attributes     [optional] Element attribute(s), default is an empty array
	 */
    public function startElement($name, array $attributes = array())
    {
        parent::startElement($name);
        $this->writeAttributes($attributes);
    }
    
    /**
     * Write an xml element
     * 
     * @param string $name          Tag name
     * @param string $content       [optional] Tag content, default is null
     * @param array $attributes     [optional] Tag attribute(s), default is an empty array
     */
    public function writeElement($name, $content = null, array $attributes = array())
    {
        $this->startElement($name);
        $this->writeAttributes($attributes);
        preg_match('~[<&]~', $content) ? $this->writeCdata($content) : $this->writeRaw($content);
        $this->endElement();
    }         
    
    /**
     * Write tag attribute(s)
     * 
     * @param array $attributes     Associative array of attribute(s)
     */
    public function writeAttributes(array $attributes)
    {
        if(empty($attributes)) return;

        foreach($attributes as $k => $v)
        {
            $this->writeAttribute($k, $v);
        }            
    }    
    
    /**
     * Returns current buffer to browser
     */
    public function output()
    {
    	header('Content-type: text/xml');
        if($this->_startDocument) $this->endDocument();
        echo $this->outputMemory();
    }
    
    /**
     * Save current buffer into a local file
     * 
     * @param string $file      File path
     * @return integer|false    Return number of bytes written or false on failure
     */
    public function toFile($file)
    {
        if($this->_startDocument) $this->endDocument();
        $xml = $this->outputMemory();
        return file_put_contents($file, $xml, LOCK_EX);
    }    
    
    /**
     * Force current buffer to download
     * 
     * @param string $fileName      [optional] Name of the file to download. Default is "output.xml"
     */
    public function download($fileName = 'output.xml')
    {
        // Clean up the output buffer
        while (ob_get_level()) ob_end_clean();        
        
        // Sets name and size
        $this->endDocument();
        $content = $this->outputMemory();
        $filesize = strlen($content);        

        // Set headers
		header('Content-type: text/xml');
		header("Content-Length: ".$filesize);
		header("Content-Disposition: attachment; filename=\"".$fileName."\"");
        header("Expires: 0");
        header("Cache-Control: no-cache, must-revalidate");
        header("Pragma: no-cache"); 

       
        echo $content;
        exit();
    }
}