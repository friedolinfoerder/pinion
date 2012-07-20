<?php
use pinion\general\Autoloader;
require_once 'pinion/php/general/Autoloader.php';
$autoloader = new Autoloader();
$autoloader->setAutoloader(array(
    "pinionAutoload",
    "moduleAutoload",
    "zendAutoload"
));
// disable ActiveRecord-Autoloader
define("PHP_ACTIVERECORD_AUTOLOAD_DISABLE", true);
?>
