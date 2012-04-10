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

class Html
{
    private static $_instance;
    
    private $_type;
    private $_doctype;
    private $_html;
    private $_meta = array();
    private $_favicon;
    private $_title;
    private $_css = array();
    private $_js = array();
    private $_content = '';
       
    /**
     * Get instance of HTML output
     * 
     * @param string $type      [optional] Type of the dtd (xhtml-strict, html5). Default is xhtml-strict
     * @param string $lang      [optional] Language of the page (ISO 639-1). Default is en
     * @return Html 
     */
    public static function getInstance($type = 'xhtml-strict', $lang = 'en')
    {
        if(self::$_instance === null) self::$_instance = new self($type, $lang);
        return self::$_instance;
    }
    
    /**
     * Constructor
     * 
     * @param string $type      [optional] Type of the dtd (xhtml-strict, html5). Default is xhtml-strict
     * @param string $lang      [optional] Language of the page (ISO 639-1). Default is en
     * @throws InvalidArgumentException 
     */
    private function __construct($type, $lang)
    {
        $this->_type = $type;
        $lang = strtolower($lang);
        
        switch ($this->_type)
        {
            case 'xhtml-strict':
                $this->_doctype = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">';
                $this->_html    = sprintf('<html xmlns="http://www.w3.org/1999/xhtml" class="%s" xml:lang="%s" lang="%s">', $this->_getHtmlClass(), $lang, $lang);
                $this->meta('Content-Type', 'text/html; charset=utf-8', 'equiv');
            break;
        
            case 'html5':
                $this->_doctype = '<!DOCTYPE html>';
                $this->_html    = sprintf('<html class="%s" lang="%s">', $this->_getHtmlClass(), $lang);
                $this->rawMeta('<meta charset="utf-8">');
            break;

            default:
                throw new InvalidArgumentException('Type '.$type.' is not a valid type');
            break;
        }        
    }
    
    /**
     * Get <html> tag css classes with browser name and version
     * @return string 
     */
    private function _getHtmlClass()
    {
        $userAgent = UserAgent::getInstance();
        $browser = strtolower($userAgent->getBrowser());
        preg_match('#^([0-9]*)[\.]?#', $userAgent->getBrowserVersion(), $matches);
        return $browser.' '.$browser.$matches[1];
    }
    
    /**
     * Add a raw meta tag
     * 
     * @param string $metatag   Ex : <meta name="description" content="My description" />
     * @return Html 
     */
    public function rawMeta($metatag)
    {
        $this->_meta[] = $metatag;
        return $this;
    }
    
    /**
     * Add a meta tag
     * 
     * @param string $name      Name of the meta
     * @param string $content   Content (value) of the meta
     * @param string $type      [optional] name or http-equiv. Default is name
     * @return Html 
     */
    public function meta($name, $content, $type = 'name')
    {
        $type = $type === 'name' ? 'name' : 'http-equiv';
        return $this->rawMeta(sprintf('<meta %s="%s" content="%s" />', $type, $name, $content));
    }
    
    /**
     * Set page title
     * 
     * @param string $title
     * @return Html
     */
    public function pageTitle($title)
    {
        $this->_title = $title;
        return $this;
    }
    
    /**
     * Set the fav icon
     * 
     * @param string $href      Path to the favicon
     * @return Html
     */
    public function favIcon($href)
    {
        $this->_favicon = sprintf('<link rel="shortcut icon" href="%s" />', $href);
        return $this;
    }
        
    /**
     * Add a css to load
     * 
     * @param string $href      Path to the css file to load
     * @param string $media     [optional] media value. Default is all
     * @return Html
     */
    public function css($href, $media = 'all')
    {
        $tag = sprintf('<link rel="stylesheet" type="text/css" media="%s" href="%s" />', $media, $href);
        if(!in_array($tag, $this->_css)) $this->_css[] = $tag;
        return $this;
    }
       
    /**
     * Add a js file to load
     * 
     * @param string $src       Path to the js file to load
     * @return Html 
     */
    public function js($src)
    {
        $tag = sprintf('<script type="text/javascript" src="%s"></script>', $src);
        if(!in_array($tag, $this->_js)) $this->_js[] = $tag;
        return $this;
    }
    
    /**
     * Add a raw js script to use
     * 
     * @param string $script    Script to use
     * @return Html
     */
    public function rawJs($script)
    {
        $this->_js[] = sprintf('<script type="text/javascript">%s</script>', $script);
        return $this;
    }
    
    /**
     * Body content
     * 
     * @param string $content   HTML content
     * @return Html 
     */
    public function content($content)
    {
        $this->_content .= $content;
        return $this;
    }
    
    /**
     * Output the html to screen or file
     * 
     * @param string $type      [optional] Type of output (screen or var). Default is screen
     * @return string|void 
     */
    public function output($type = 'screen')
    {
        $res = array();
        
        $res[] = $this->_doctype;
        $res[] = $this->_html;
        $res[] = '<head>';
                
        foreach($this->_meta as $meta)  $res[] = $meta;                        
        
        $res[] = '<title>'.$this->_title.'</title>';
        
        if($this->_favicon !== null) $res[] = $this->_favicon;
        
        foreach($this->_css as $css)  $res[] = $css;
        
        $res[] = '</head>';
        $res[] = '<body>';
        $res[] = $this->_content;
        
        foreach($this->_js as $js)  $res[] = $js;
        
        $res[] = '</body>';        
        $res[] = '</html>';
        
        $html = join(PHP_EOL, $res);
        
        if($type != 'screen') return $html;
        
        echo $html;
    }
}