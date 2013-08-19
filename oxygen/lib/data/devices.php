<?php

$devices = array(
    "iphone|ipod" => array("type" => "phone", "value" => "iphone"),
    "android.*mobile" => array("type" => "phone", "value" => "android"),
    "ipad" => array("type" => "tablet", "value" => "ipad"),
    "android(?!.*mobile)" => array("type" => "tablet", "value" => "androidtablet"),
    "windows phone os" => array("type" => "phone", "value" => "windowsphone"),
    "blackberry" => array("type" => "phone", "value" => "blackberry"),
    "samsung|bada" => array("type" => "phone", "value" => "samsung"),
    "avantgo|blazer|elaine|hiptop|palm|plucker|xiino" => array("type" => "phone", "value" => "palm"),
    "windows ce; (iemobile|ppc|smartphone)" => array("type" => "phone", "value" => "windows"),
    "rim tablet os" => array("type" => "tablet", "value" => "blackberrytablet"),
    "meego|nokia" => array("type" => "phone", "value" => "nokia"),
    "tablet(?!.*PC)" => array("type" => "tablet", "value" => "generic"),
    "kindle|mobile|mmp|midp|pocket|psp|symbian|symbos|smartphone|treo|up.browser|up.link|vodafone|wap|opera mini" => array("type" => "phone", "value" => "generic")
);