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

// Load the framework
require_once('../oxygen/bootstrap.php');

if(isset($_GET['asset']))
{
    $asset = Asset::getInstance();
    $assets = explode(',', $_GET['asset']);
    foreach($assets as $a) $asset->add($a);
    $asset->output();   
}
else
{
    // Run the dispatcher
    Controller::getInstance()->dispatch();    
}
