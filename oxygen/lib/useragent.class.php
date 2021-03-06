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
class UserAgent
{

    private static $instance;
    private $_agent;                 // user agent full string
    private $_accept;                // accepted types
    private $_languages = array();   // array of accepted languages
    private $_charsets = array();    // array of accepted charsets

    /**
     * Return singleton instance
     * 
     * @return UserAgent
     */
    public static function getInstance()
    {
        if (!isset(self::$instance)) self::$instance = new self();
        return self::$instance;
    }

    /**
     * Constructor
     */
    protected function __construct()
    {
        if (isset($_SERVER['HTTP_USER_AGENT'])) $this->_agent = trim($_SERVER['HTTP_USER_AGENT']);
        if (isset($_SERVER['HTTP_ACCEPT'])) $this->_accept = trim($_SERVER['HTTP_ACCEPT']);

        $this->_setLanguages();
        $this->_setCharsets();
    }

    public function __toString()
    {
        return $this->_agent;
    }

    // -------------------------------------------- GENERAL / DETECTION

    public function getAgentString()
    {
        return $this->_agent;
    }

    // -------------------------------------------- LANGUAGES

    /**
     * Get the accepted languages
     * 
     * @return array 
     */
    public function getLanguages()
    {
        return empty($this->_languages) ? $this->_setLanguages() : $this->_languages;
    }

    /**
     * Get the first accepted language
     * 
     * @return string       The first language in ISO 639-1 format else "undefined"
     */
    public function getLanguage()
    {
        if (empty($this->_languages)) $this->_setLanguages();
        return preg_replace('/-(.*)$/i', '', first($this->_languages));
    }

    /**
     * Check if the browser accepts the given language
     * 
     * @param string $language      ISO 639-1 or ISO 639-1 with ISO3166-1, ex : "fr", "fr_FR", "fr-FR", "fr-fr"
     * @return boolean              Return true if language is supported by the browser
     */
    public function acceptLanguage($language)
    {
        $language = strtolower(str_replace('_', '-', $language));
        if (empty($this->_languages)) $this->_setLanguages();
        return in_array($language, $this->_languages);
    }

    // -------------------------------------------- CHARSETS

    /**
     * Get the accepted charsets
     * 
     * @return array 
     */
    public function getCharsets()
    {
        return empty($this->_charsets) ? $this->_setCharsets() : $this->_charsets;
    }

    /**
     * Get the first accepted charset
     * 
     * @return string 
     */
    public function getCharset()
    {
        return first(empty($this->_charsets) ? $this->_setCharsets() : $this->_charsets);
    }

    /**
     * Check if the browser accpets the given charset
     * 
     * @param string $charset       The charset to test
     */
    public function acceptCharset($charset)
    {
        if (empty($this->_charsets)) $this->_setCharsets();
        return in_array($charset, $this->_charsets);
    }

    // -------------------------------------------- REFERRER

    /**
     * Get the referrer
     * 
     * @return string 
     */
    public function getReferrer()
    {
        return (!isset($_SERVER['HTTP_REFERER']) || $_SERVER['HTTP_REFERER'] == '') ? '' : trim($_SERVER['HTTP_REFERER']);
    }

    /**
     * Is this a referral from another site ?
     * 
     * @return boolean 
     */
    public function isReferral()
    {
        return (!isset($_SERVER['HTTP_REFERER']) || $_SERVER['HTTP_REFERER'] == '') ? false : true;
    }

// -------------------------------------------- PLATFORM

    /**
     * Get the current platform
     * 
     * @return string 
     */
    public function getPlatform()
    {
        if (!isset($this->platform))
        {
            $this->platform = 'undefined';

            include(FW_DIR . DS . 'lib' . DS . 'data' . DS . 'platforms.php');

            foreach ($platforms as $rule => $platform)
            {
                if (preg_match("#" . preg_quote($rule) . "#i", $this->_agent, $match))
                {
                    $this->platform = $platform;
                    break;
                }
            }
        }

        return $this->platform;
    }

    // -------------------------------------------- BROWSER

    /**
     * Get the current browser
     * 
     * @return string 
     */
    public function getBrowser()
    {
        if (!isset($this->browser))
        {
            $this->browser = 'undefined';
            $this->isBrowser = false;

            include(FW_DIR . DS . 'lib' . DS . 'data' . DS . 'browsers.php');
            foreach ($browsers as $rule => $browser)
            {
                if (preg_match("#" . preg_quote($rule) . ".*?([0-9\.]+)#i", $this->_agent, $match))
                {
                    $this->isBrowser = true;
                    $this->browser = $browser;
                    $this->version = $match[1];
                    break;
                }
            }
        }

        return $this->browser;
    }

    /**
     * Get the current browser version
     * 
     * @return string 
     */
    public function getBrowserVersion()
    {
        if (!isset($this->isBrowser)) $this->getBrowser();
        return isset($this->version) ? $this->version : null;
    }

    // -------------------------------------------- ROBOTS    

    /**
     * Get the robot name if current user agent is a robot
     * 
     * @return string       The robot full name or an empty string
     */
    public function getRobotName()
    {
        return $this->isRobot() ? $this->robot : '';
    }

    /**
     * Is current user agent a robot ?
     * 
     * @see    oxygen/lib/xml/robots.xml
     * @return boolean
     */
    public function isRobot()
    {
        if (!isset($this->isRobot))
        {
            $this->isRobot = false;

            include(FW_DIR . DS . 'lib' . DS . 'data' . DS . 'robots.php');

            foreach ($robots as $rule => $robot)
            {
                /* @var $robot SimpleXMLElement */
                if (preg_match("#" . preg_quote($rule) . "#i", $this->_agent))
                {
                    $this->isRobot = true;
                    $this->robot = $robot;
                    return true;
                }
            }
        }
        return $this->isRobot;
    }

    // -------------------------------------------- MOBILE    

    /**
     * Is current device a tablet ?
     * 
     * @return boolean
     */
    public function isTablet()
    {
        if (!$this->isMobile()) return false;
        return isset($this->deviceType) ? $this->deviceType == 'tablet' : false;
    }

    /**
     * Check if the current user agent is a mobile from the given type
     * 
     * @param string $deviceName    Name of the device to check (iPad, iPhone, Android, etc...)
     * @return boolean 
     */
    public function isDevice($deviceName)
    {
        if (!$this->isMobile()) return false;
        return isset($this->device) ? $this->device == strtolower($deviceName) : false;
    }

    /**
     * Is current device a mobile device ?
     * 
     * @return boolean 
     */
    public function isMobile()
    {
        if (!isset($this->isMobile))
        {
            $this->isMobile = false;

            if (isset($_SERVER['HTTP_X_WAP_PROFILE']) || isset($_SERVER['HTTP_PROFILE']))
            {
                $this->device = 'generic';
                $this->deviceType = 'phone';
                $this->isMobile = true;
            }
            elseif (strpos($this->_accept, 'text/vnd.wap.wml') > 0 || strpos($this->_accept, 'application/vnd.wap.xhtml+xml') > 0)
            {
                $this->device = 'generic';
                $this->deviceType = 'phone';
                $this->isMobile = true;
            }
            else
            {
                include(FW_DIR . DS . 'lib' . DS . 'data' . DS . 'devices.php');

                foreach ($devices as $rule => $device)
                {
                    if (preg_match('/' . $rule . '/i', $this->_agent))
                    {
                        $this->device = $device['value'];
                        $this->deviceType = $device['type'];
                        $this->isMobile = true;
                        break;
                    }
                }
            }
        }

        return $this->isMobile;
    }

    // -------------------------------------------- PRIVATE METHODS

    /**
     * Set the browser accepted languages to private var
     * 
     * @return array        Array of accepted languages
     */
    private function _setLanguages()
    {
        if ((empty($this->_languages)) && isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) && $_SERVER['HTTP_ACCEPT_LANGUAGE'] != '')
        {
            $languages = preg_replace('/(;q=[0-9\.]+)/i', '', strtolower(trim($_SERVER['HTTP_ACCEPT_LANGUAGE'])));
            $this->_languages = explode(',', $languages);
        }

        if (empty($this->_languages)) $this->_languages = array('undefined');

        return $this->_languages;
    }

    /**
     * Set the browser accepted charsets to private var
     * 
     * @return array        Array of accepted charsets
     */
    private function _setCharsets()
    {
        if (empty($this->_charsets) && isset($_SERVER['HTTP_ACCEPT_CHARSET']) && $_SERVER['HTTP_ACCEPT_CHARSET'] != '')
        {
            $charsets = preg_replace('/(;q=.+)/i', '', strtolower(trim($_SERVER['HTTP_ACCEPT_CHARSET'])));
            $this->_charsets = explode(',', $charsets);
        }

        if (empty($this->_charsets)) $this->_charsets = array('undefined');

        return $this->_charsets;
    }

}