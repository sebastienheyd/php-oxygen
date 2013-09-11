<?php
/**
 * Add a link tag to the given CSS file(s)
 * 
 * @param array $params
 * @param Template $smarty
 * @return string
 */
function smarty_function_style($params, &$smarty)
{
    $files = explode(',', $params['href']);    
    if(empty($files)) return '';

    if(Config::get('asset.combine', true) === false)
    {
        foreach($files as $file) echo '<link rel="stylesheet" type="text/css" href="'.$file.'" />';
    }
    else
    {
        $asset = Asset::getInstance();
        foreach($files as $file) $asset->add($file);
        $asset->compile();

        echo '<link rel="stylesheet" type="text/css" href="/'.$asset->getUid().'.css" />';        
    }
}
