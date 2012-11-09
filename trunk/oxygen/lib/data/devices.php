<?php

$devices = array(
    "iphone|ipod" => array("type" => "phone", "value" => "iphone"),
    "android.*mobile" => array("type" => "phone", "value" => "android"),
    "blackberry" => array("type" => "phone", "value" => "blackberry"),
    "samsung|bada" => array("type" => "phone", "value" => "samsung"),
    "meego|nokia" => array("type" => "phone", "value" => "nokia"),
    "avantgo|blazer|elaine|hiptop|palm|plucker|xiino" => array("type" => "phone", "value" => "palm"),
    "windows ce; (iemobile|ppc|smartphone)" => array("type" => "phone", "value" => "windows"),
    "windows phone os" => array("type" => "phone", "value" => "windowsphone"),
    "kindle|mobile|mmp|midp|pocket|psp|symbian|symbos|smartphone|treo|up.browser|up.link|vodafone|wap|opera mini" => array("type" => "phone", "value" => "generic"),
    "ipad" => array("type" => "tablet", "value" => "ipad"),
    "tablet" => array("type" => "tablet", "value" => "generic"),
    "android(?!.*mobile)" => array("type" => "tablet", "value" => "androidtablet"),
    "rim tablet os" => array("type" => "tablet", "value" => "blackberrytablet")
);