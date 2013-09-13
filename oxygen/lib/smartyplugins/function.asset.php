<?php

/**
 * Add an html tag for the given asset(s)
 * 
 * @param array $params
 * @param Template $smarty
 * @return string
 */
function smarty_function_asset($params, &$smarty)
{
    if(!isset($params['href'])) throw new SmartyException('No parameter href is set');

    // Getting files list
    $files = explode(',', $params['href']);
    if(empty($files)) throw new SmartyException('href parameter is empty');

    $dir = '';
    if(isset($params['dir'])) $dir = '/' . trim($params['dir'], '/') . '/';

    // Getting file type
    if(!isset($params['type']))
    {
        $ext = substr($files[0], strrpos($files[0], '.') + 1);
        switch($ext)
        {
            case 'less':
            case 'css':
                $type = 'css';
                break;

            case 'js':
                $type = 'js';
                break;
        }
    }
    else
    {
        $type = $params['type'];
    }

    if(!isset($type) || ($type !== 'js' && $type !== 'css')) throw new SmartyException('Type is not defined');

    if(Config::get('asset.combine', true) === false || count($files) === 1) // combining is off
    {
        foreach($files as $file)
        {
            $timestamp = Asset::getInstance()->add($dir . $file)->getLastModified();
            if($type === 'css')
            {
                echo '<link rel="stylesheet" type="text/css" href="/' . $timestamp . $dir . $file . '" />' . PHP_EOL;
            }
            else
            {
                echo '<script src="/' . $timestamp . $dir . $file . '"></script>' . PHP_EOL;
            }
        }
    }
    else  // combining is on
    {
        $asset = Asset::getInstance();
        foreach($files as $file) $asset->add($dir . $file);
        $asset->compile();

        if($type === 'css')
        {
            echo '<link rel="stylesheet" type="text/css" href="/' . $asset->getUid() . '.css" />' . PHP_EOL;
        }
        else
        {
            echo '<script src="/' . $asset->getUid(). '.js"></script>' . PHP_EOL;
        }
    }
}
