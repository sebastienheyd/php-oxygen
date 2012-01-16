#!/usr/bin/php -q
<?php

require_once('oxygen/bootstrap.php');

putenv('UNIQUID=toto');

$cli = Cli::getInstance();


if($cli->confirm($cli->getString('Launching tests', 'cyan')))
{
    $cli->printf('toto', 'red');
}                

