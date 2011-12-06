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


class Email
{
    private $_from;
    private $_returnPath;
    private $_to = array();
    private $_cc = array();
    private $_bcc = array();
    private $_subject;
    private $_bodyText;
    private $_bodyHtml;
    private $_frontier;
    private $_attachment;
    private static $_line = "\n";
 
    /**
     * Main constructor
     */
    private function __construct()
    {
        $this->_frontier = md5(uniqid(mt_rand()));
    }
 
    /**
     * Sets the email address(es) of the recipient(s).<br />Can be a single email, a comma-delimited list or an array.
     *
     * @return Email    Return current instance of Email
     */
    public static function to()
    {
        if(func_num_args() == 0) throw new Exception ('No recipient defined');
        
        $inst = new self();        
        
        $args = func_get_args();
 
        if(!empty($args))
        {
            foreach ($args as $arg)
            {
                if(is_array($arg)) $arg = join(',', $arg);
                $to = explode(',', $arg);
                $inst->_to = array_unique(array_merge($inst->_to, array_map('trim', $to)));
            }
        }
 
        return $inst;
    }
 
    /**
     * Sets the email address and optionnaly the name of the person sending the email
     *
     * @param string $emailAddress  E-mail address of the person sending the mail.
     * @param string $name          [optional] Name of the person sending the mail, default is null.
     * @return Email                Return current instance of Email
     */
    public function from($emailAddress, $name = null)
    {
        $value = !is_null($name) ? $name.' <'.$emailAddress.'>':$emailAddress;
        $this->_from = $value;
        $this->_returnPath = $emailAddress;
        return $this;
    }
    
    /**
     * Sets the email address(es) of the carbon copy recipient(s).<br />Can be a single email, a comma-delimited list or an array.
     * 
     * @return Email                Return current instance of Email
     */
    public function cc()
    {
        if(func_num_args() > 0)
        {
            $args = func_get_args();

            foreach ($args as $arg)
            {
                if(is_array($arg)) $arg = join(',', $arg);
                $cc = explode(',', $arg);
                $this->_cc = array_unique(array_merge($this->_cc, array_map('trim', $cc)));
            }
        }
        
        return $this;
    }
    
    /**
     * Sets the email address(es) of the blind carbon copy recipient(s).<br />Can be a single email, a comma-delimited list or an array.
     * 
     * @return Email                Return current instance of Email
     */
    public function bcc()
    {
        if(func_num_args() > 0)
        {
            $args = func_get_args();

            foreach ($args as $arg)
            {
                if(is_array($arg)) $arg = join(',', $arg);
                $bcc = explode(',', $arg);
                $this->_bcc = array_unique(array_merge($this->_bcc, array_map('trim', $bcc)));
            }          
        }
        
        return $this;
    }
    
    /**
     * Alias of bcc
     * 
     * @return Email                Return current instance of Email
     */
    public function cci()
    {
        $args = func_get_args();
        return call_user_func_array(array($this, 'bcc'), $args);
    }
 
    /**
     * Sets the email subject
     *
     * @param string $subject   The e-mail subject
     * @return Email            Return current instance of Email
     */
    public function subject($subject)
    {
        $this->_subject = $subject;
        return $this;
    }
 
    /**
     * Sets the email message body in raw text format
     *
     * @param string $text  The message body in raw text format
     * @return Email        Return current instance of Email
     */
    public function bodyText($text)
    {
        $this->_bodyText = $text;
        return $this;
    }
 
    /**
     * Sets the email message body in html format
     *
     * @param string $html  The message body in html format
     * @return Email        Return current instance of Email
     */
    public function bodyHtml($html)
    {
        $this->_bodyHtml = $html;
        return $this;
    }
 
    /**
     * Add an attachment file to the mail.
     *
     * @param string $file          A file from the server to join to the mail
     * @param string $fileName      [optional] Name of the added file in the mail, default is null. If null take the given file name
     * @param string $mimeType      [optional] Forced mimetype, default is null. If null take the given file mime type
     * @return Email                Return current instance of Email
     */
    public function addAttachment($file, $fileName = null, $mimeType = null)
    {
        $file = File::load($file);
        $file->isInDir();
        
        $mimeType = is_null($mimeType) ? $file->getMimeType() : $mimeType;
        $fileName = is_null($fileName) ? $file->getFileName() : $fileName;
        
        $this->addStringAttachment(file_get_contents($file), $fileName, $mimeType);
        return $this;
    }
 
    /**
     * Add a string as an attachment file with the mail
     *
     * @param string $contentString         Content of the attachment file
     * @param string $attachmentFileName    Name of the attachment file
     * @param string $mimeType              [optional] Mime-type of the attachment file, default is null. If null will get from the given filename extension.
     * @return Email                        Return current instance of Email
     */
    public function addStringAttachment($contentString, $fileName, $mimeType = null)
    {
        if(is_null($mimeType)) $mimeType = File::getMimeTypeFrom($fileName);        
        $a['mimeType'] = $mimeType;
        $a['name'] = $fileName;
        $a['content'] = $contentString;
        $this->_attachment[] = $a;
        return $this;
    }
 
    /**
     * The mail has a file in it ?
     *
     * @return boolean      Return true if current mail has an attached file
     */
    private function _hasAttachment()
    {
        return !empty($this->_attachment);
    }
 
    /**
     * Send the mail
     *
     * @param boolean       [optional] Must the sender use the return path option, default is true. Use only with sendmail
     * @return boolean      Return true if success
     */
    public function send($useReturnPath = true)
    {
        if (($this->_bodyText != '' || $this->_bodyHtml != '') && isset($this->_to) && isset($this->_from))
        {
            if(version_compare(phpversion(), '5.3.0', '>=') || ini_get("safe_mode") || $useReturnPath == false)
            {
                return mail(join(',', $this->_to), '=?UTF-8?B?'.base64_encode($this->_subject).'?=', $this->_buildMessage(), $this->_buildHeader());
            }
            else
            {
                return mail(join(',', $this->_to), '=?UTF-8?B?'.base64_encode($this->_subject).'?=', $this->_buildMessage(), $this->_buildHeader(), '-f'.$this->_returnPath);
            }
        }
        return false;
    }
 
    /**
     * Build the mail header
     *
     * @return string           Return the builded header
     */
    private function _buildHeader()
    {
        $header = 'From: ' . $this->_from . self::$_line;
        $header .= 'Reply-To: ' . $this->_from . self::$_line;
        $header .= 'X-Sender: ' . $this->_from . self::$_line;
        $header .= 'X-Mailer:PHP/' . phpversion() . self::$_line;
                
        if(!empty($this->_cc))  $header .= 'Cc: '.join(',', $this->_cc).self::$line;
        if(!empty($this->_bcc)) $header .= 'Bcc: '.join(',', $this->_bcc).self::$line;        
 
        if ($this->_isMultiPart() || $this->_hasAttachment())
        {
            $header .= 'MIME-Version: 1.0' . self::$_line;
        }
 
        if ($this->_hasAttachment())
        {
            $header .= 'Content-Type: multipart/mixed; boundary="multi-' . $this->_frontier . '"' . self::$_line;
        }
        else
        {
            if ($this->_isMultiPart())
            {
                $header .= 'Content-Type: multipart/alternative; boundary="alt-' . $this->_frontier . '"' . self::$_line;
            }
            else if (isset($this->_bodyText) && !is_null($this->_bodyText))
            {
                $header .= 'Content-Type: text/plain; charset="utf-8"' . self::$_line;
            }
            else
            {
                $header .= 'Content-Type: text/html; charset="utf-8"' . self::$_line;
            }
        }
 
        $header .= 'Content-Transfer-Encoding: base64' . self::$_line . self::$_line. self::$_line . self::$_line;
 
        return $header;
    }
 
    /**
     * Build the mail message
     *
     * @return string           Return the builded message
     */
    private function _buildMessage()
    {
        $message = '';
        
        if($this->_isMultiPart()) // mail is in plain text and html
        {
            // mail has an attachment, we must declare
            if ($this->_hasAttachment())
            {
                $message .= '--multi-' . $this->_frontier . self::$_line;
                $message .= 'Content-Type: multipart/alternative; boundary="alt-' . $this->_frontier . '"' . self::$_line . self::$_line;
            }
 
            // plain text message
            $message .= '--alt-' . $this->_frontier . self::$_line;
            $message .= 'Content-Type: text/plain; charset="utf-8"' . self::$_line;
            $message .= 'Content-Transfer-Encoding: base64' . self::$_line . self::$_line;
            $message .= chunk_split(base64_encode($this->_bodyText)) . self::$_line . self::$_line;
 
            // html message
            $message .= '--alt-' . $this->_frontier . self::$_line;
            $message .= 'Content-Type: text/html; charset="utf-8"' . self::$_line;
            $message .= 'Content-Transfer-Encoding: base64' . self::$_line . self::$_line;
            $message .= chunk_split(base64_encode($this->_bodyHtml)) . self::$_line . self::$_line;
        }
        else if (isset($this->_bodyText)) // mail is only in plain text
        {
            if ($this->_hasAttachment())
            {
                $message .= '--multi-' . $this->_frontier . self::$_line;
                $message .= 'Content-Type: text/plain; charset="utf-8"' . self::$_line;
                $message .= 'Content-Transfer-Encoding: base64' . self::$_line . self::$_line;
            }
 
            $message .= chunk_split(base64_encode($this->_bodyText));
        }
        else  // mail is only in html
        {
            if ($this->_hasAttachment())
            {
                $message .= '--multi-' . $this->_frontier . self::$_line;
                $message .= 'Content-Type: text/html; charset="utf-8"' . self::$_line;
                $message .= 'Content-Transfer-Encoding: base64' . self::$_line . self::$_line;
            }
 
            $message .= chunk_split(base64_encode($this->_bodyHtml));
        }
        
        if ($this->_hasAttachment()) // we have a file attached to the mail
        {
            foreach ($this->_attachment as $attachment)
            {
                $message .= '--multi-' . $this->_frontier . self::$_line;
                $message .= 'Content-Type: ' . $attachment['mimeType'] . '; name="' . $attachment['name'] . '"' . self::$_line;
                $message .= 'Content-Transfer-Encoding: base64' . self::$_line;
                $message .= 'Content-Disposition:attachement; filename="' . $attachment['name'] . '"' . self::$_line . self::$_line;
                $message .= chunk_split(base64_encode($attachment['content']));
            }
        }
 
        return $message;
    }
 
    /**
     * Check if mail is in raw text AND html
     *
     * @return boolean      Return true if current mail is multipart (mix of raw text and html)
     */
    private function _isMultiPart()
    {
        return isset($this->_bodyText) && isset($this->_bodyHtml) && !is_null($this->_bodyText) && !is_null($this->_bodyHtml);
    }
}
 