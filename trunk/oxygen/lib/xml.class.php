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

class XML
{
    /**
     * @return f_xml_Write 
     */
    public static function writer($startDocument = true)
    {
        return f_xml_Writer::getInstance($startDocument);
    }
}