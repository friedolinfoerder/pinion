<?php
use pinion\data\database\DBActiveRecordAdminister;
use pinion\data\database\NoDatabaseInformationException;
use pinion\data\database\ActiveRecord;
use pinion\events\EventDispatcher;
use pinion\files\classwriter\AR_Class_Writer;

// classWriter
$classWriter = new AR_Class_Writer();
$classWriter
    ->setNamespace("pinion\\data\\models")
    ->setUses(array("\\pinion\\data\\database\\ActiveRecord"));

try {
    $dbAdminister = new DBActiveRecordAdminister($classWriter, MODELS_PATH, "\\pinion\\data\\models\\", "database.ini");
    $salt = $dbAdminister->getSalt();
    ActiveRecord::$eventObject = new EventDispatcher();
} catch(NoDatabaseInformationException $exception) {
    require_once 'install.php';
    die();
}
?>
