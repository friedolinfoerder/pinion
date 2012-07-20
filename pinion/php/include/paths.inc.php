<?php
$modulesPath = "modules";
$templatesPath = "templates";

$siteUrl = dirname($_SERVER["SCRIPT_NAME"]);
if($siteUrl == "\\")
    $siteUrl = "";

define("PINION_URL", "http://www.pinion-cms.org");

define("SITE_URL", $siteUrl."/");
define("MODULES_URL", SITE_URL.$modulesPath);
define("TEMPLATES_URL", SITE_URL.$templatesPath);

define("APPLICATION_PATH", dirname($_SERVER["SCRIPT_FILENAME"])."/");
define("MODULES_PATH", APPLICATION_PATH.$modulesPath."/");
define("TEMPLATES_PATH", APPLICATION_PATH.$templatesPath."/");
define("MODELS_PATH", APPLICATION_PATH."pinion/php/data/models/");

define("MODELS_NAMESPACE", "\\pinion\\data\\models\\");

// set include path
set_include_path(get_include_path() . PATH_SEPARATOR 
    . APPLICATION_PATH."/pinion/thirdparty" . PATH_SEPARATOR
    . APPLICATION_PATH);
?>
