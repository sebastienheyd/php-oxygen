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


class Email
{
    private $_from;
    private $_returnPath;
    private $_messageId;
    private $_reference;
    private $_to = array();
    private $_cc = array();
    private $_bcc = array();
    private $_priorities = array( 1 => '1 (Highest)', '2 (High)', '3 (Normal)', '4 (Low)', '5 (Lowest)' );
    private $_priority;
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
     * Sets the email address(es) of the recipient(s).<br />
     * Can be a single email, a comma, dot-comma, space separated list or an array.
     *
     * @return Email    Return current instance of Email
     */
    public static function to()
    {
        if(func_num_args() === 0) throw new Exception ('No recipient defined');
        
        $inst = new self();
 
        $args = func_get_args();
        
        foreach ($args as $arg)
        {
            if(is_array($arg)) $arg = join(',', $arg);
            $to = preg_split("/[\s,;]+/", $arg);
            $inst->_to = array_unique(array_merge($inst->_to, array_map('trim', $to)));
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
        $value = $name !== null ? '"'.String::stripAccents($name).'" <'.$emailAddress.'>':$emailAddress; // Strip accents 
        $this->_from = $value;
        $this->_returnPath = $emailAddress;
        return $this;
    }
    
    /**
     * Define the mail priority.
     * 
     * @param integert $priority
     * @return Email                Return current instance of Email
     */
    public function priority($priority)
    {
        if(intval($priority) && isset( $this->_priorities[$priority])) $this->_priority= $this->_priorities[$priority];
        return $this;
    }    
    
    /**
     * Sets the email address(es) of the carbon copy recipient(s).<br />Can be a single email, a comma-delimited list or an array.
     * 
     * @return Email                Return current instance of Email
     */
    public function cc()
    {
        if(func_num_args() === 0) return $this;

        $args = func_get_args();
        
        foreach ($args as $arg)
        {
            if(is_array($arg)) $arg = join(',', $arg);
            $cc = explode(',', $arg);
            $this->_cc = array_unique(array_merge($this->_cc, array_map('trim', $cc)));
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
        if(func_num_args() === 0) return $this;

        $args = func_get_args();
        
        foreach ($args as $arg)
        {
            if(is_array($arg)) $arg = join(',', $arg);
            $bcc = explode(',', $arg);
            $this->_bcc = array_unique(array_merge($this->_bcc, array_map('trim', $bcc)));
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
     * Sets the message ID
     * 
     * @param string $id        Id of the e-mail
     * @param string $suffix    [optional] Id suffix. Default = email
     * @param string $host      [optional] Host address
     * @return Email
     */
    public function messageId($id, $suffix = 'email', $host = null)
    {
        if($host === null) $host = $_SERVER['HTTP_HOST'];
        $this->_messageId = md5($id).'.'.$suffix.'@'.$host;
        return $this;
    }
    
    /**
     * Sets the reference to an e-mail
     * 
     * @param string $id        Id of the referenced e-mail
     * @param string $suffix    [optional] Id suffix. Default = email
     * @param string $host      [optional] Host address
     * @return Email
     */
    public function reference($id, $suffix = 'email', $host = null)
    {
        if($host === null) $host = $_SERVER['HTTP_HOST'];
        $this->_reference = md5($id).'.'.$suffix.'@'.$host;    
        return $this;
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
        if($file = File::load($file))
        {
            $mimeType = $mimeType === null ? $file->getMimeType() : $mimeType;
            $fileName = $fileName === null ? $file->getFileName() : $fileName;

            $this->addStringAttachment($file->getContents(), $fileName, $mimeType);
        }
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
        if($mimeType === null) $mimeType = File::getMimeTypeFrom($fileName);        
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
        if (($this->_bodyText !== '' || $this->_bodyHtml !== '') && !empty($this->_to) && !empty($this->_from))
        {
            if(version_compare(phpversion(), '5.3.0', '>=') || ini_get("safe_mode") || $useReturnPath == false)
            {
                return mail(join(',', $this->_to), $this->_b($this->_subject), $this->_buildMessage(), $this->_buildHeaders());
            }
            else
            {
                return mail(join(',', $this->_to), $this->_b($this->_subject), $this->_buildMessage(), $this->_buildHeaders(), '-f'.$this->_returnPath);
            }
        }
        return false;
    }
 
    /**
     * Build the mail header
     *
     * @return string           Return the builded header
     */
    private function _buildHeaders()
    {
        $h = array();        
        $h[] = 'From: ' . $this->_from;
        $h[] = 'Reply-To: ' . $this->_from;
        $h[] = 'X-Sender: ' . $this->_from;
        $h[] = 'X-Mailer:PHP/' . phpversion();
                
        if(!empty($this->_cc))          $h[] = 'Cc: '.join(',', $this->_cc);
        if(!empty($this->_bcc))         $h[] = 'Bcc: '.join(',', $this->_bcc);     
        if(isset($this->_messageId))    $h[] = 'Message-ID: '.$this->_messageId;
        if(isset($this->_reference))    $h[] = 'References: ' . $this->_reference;
        if(isset($this->_priority))     $h[] = 'X-Priority: ' . $this->_priority;
        
        $h[] = 'MIME-Version: 1.0';
        
        if ($this->_isMultiPart() || $this->_hasAttachment())
        {                      
            $h[] = 'Content-Type: multipart/alternative; boundary=alt-' . $this->_frontier;
        }
        else
        {
            $type = isset($this->_bodyText) ? 'plain':'html';
            $h[] = 'Content-Type: text/'.$type.'; charset="utf-8"';
            $h[] = 'Content-Transfer-Encoding: base64' . self::$_line;
        }

        return join(self::$_line, $h);
    }
 
    /**
     * Build the mail message
     *
     * @return string           Return the builded message
     */
    private function _buildMessage()
    {
        $m = array();
        
        if($this->_isMultiPart()) // mail is in plain text and html
        { 
            // plain text message
            $m[] = '--alt-' . $this->_frontier;
            $m[] = 'Content-Type: text/plain; charset="utf-8"';
            $m[] = 'Content-Transfer-Encoding: base64' . self::$_line;
            $m[] = chunk_split(base64_encode($this->_bodyText)) . self::$_line;
 
            // html message
            $m[] = '--alt-' . $this->_frontier;
            
             // mail has an attachment, we must declare
            if ($this->_hasAttachment())
            {
                $m[] = 'Content-Type: multipart/mixed; boundary=multi-' . $this->_frontier . self::$_line;
                $m[] = '--multi-' . $this->_frontier;
            }
            
            $m[] = 'Content-Type: text/html; charset="utf-8"';
            $m[] = 'Content-Transfer-Encoding: base64' . self::$_line;
            $m[] = chunk_split(base64_encode($this->_bodyHtml)) . self::$_line;
        }
        else // mail is only in plain text or only in html
        {       
            if ($this->_hasAttachment())
            {
                $m[] = '--alt-' . $this->_frontier;
                
                $m[] = 'Content-Type: multipart/mixed; boundary=multi-' . $this->_frontier . self::$_line;
                $m[] = '--multi-' . $this->_frontier;
                
                $type = isset($this->_bodyText) ? 'plain':'html';
                $m[] = 'Content-Type: text/'.$type.'; charset="utf-8"';            
                $m[] = 'Content-Transfer-Encoding: base64' . self::$_line;
            }
             
            $content = isset($this->_bodyText) ? $this->_bodyText:$this->_bodyHtml;            
            $m[] = chunk_split(base64_encode($content)). self::$_line;;
        }
        
        if ($this->_hasAttachment()) // we have a file attached to the mail
        {
            foreach ($this->_attachment as $attachment)
            {
                $m[] = '--multi-' . $this->_frontier;                    
                $m[] = 'Content-Type: ' . $attachment['mimeType'] . '; name="' . $this->_b($attachment['name']) . '"';
                $m[] = 'Content-Transfer-Encoding: base64';
                $m[] = 'Content-Disposition: attachment; filename="' . $this->_b($attachment['name']) . '"' . self::$_line;
                $m[] = chunk_split(base64_encode($attachment['content']));
            }
        }
 
        return join(self::$_line, $m);
    }
 
    /**
     * Check if mail is in raw text AND html
     *
     * @return boolean      Return true if current mail is multipart (mix of raw text and html)
     */
    private function _isMultiPart()
    {
        return isset($this->_bodyText) && isset($this->_bodyHtml);
    }
    
    /**
     * Encode the string to utf-8 base64 compliant string
     * 
     * @param string $string    The utf-8 string
     * @return string           Base 64 string in utf-8
     */
    private function _b($string)
    {
        return '=?UTF-8?B?'.base64_encode($string).'?=';
    }
}
 