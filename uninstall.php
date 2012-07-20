<?php

define("APPLICATION_PATH", dirname($_SERVER["SCRIPT_FILENAME"])."/");
define("MODELS_PATH", APPLICATION_PATH."pinion/php/data/models/");

spl_autoload_register();



$settings = parse_ini_file("pinion/php/data/database/database.ini", true);
$settings = $settings['database'];

$driver = $settings['driver'];
$dbname = $settings['name'];
$host = $settings['host'];
$username = $settings['username'];
$password = $settings['password'];
$prefix = $settings['prefix'];

$prefixLength = strlen($prefix);

$pdo = new PDO("$driver:host=$host;dbname=$dbname", $username, $password);
$tables = $pdo->query("SHOW TABLES");

$pdo->exec('SET foreign_key_checks = 0');
foreach($tables as $table) {
    if(substr($table["Tables_in_cms_pinion"], 0, $prefixLength) == $prefix) {
        $pdo->exec("DROP TABLE `{$table["Tables_in_cms_pinion"]}`");
    }
}
$pdo->exec('SET foreign_key_checks = 1');

//Remove all models from current models directory
$modelsPath = new DirectoryIterator(MODELS_PATH);
foreach ($modelsPath as $file) {
    if($file->isFile()) {
        unlink($file->getRealPath());
    }
}

unlink("pinion/php/data/database/database.ini");

?>
