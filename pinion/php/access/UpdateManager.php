<?php
/**
 * The UpdateManager can update the components of the cms
 * 
 * PHP version 5.3
 * 
 * @category   access
 * @package    access
 * @subpackage access
 * @author     Friedolin Förder <friedolin.foerder@pinion-cms.org>
 * @license    MIT License
 * @link       http://www.pinion-cms.org
 */


namespace pinion\general;

use \pinion\files\DirectoryRearranger;


class UpdateManager {
    
    /**
     * Function for updating a module
     * 
     * @param string $identifier The module identifier string
     * @param string $directory  The path to the directory with the files to update
     */
    public static function updateModule($identifier, $directory) {
        $strlenDir = strlen($directory);
        $dirIterator = new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS);

        $objects = new \RecursiveIteratorIterator($dirIterator, \RecursiveIteratorIterator::SELF_FIRST);
        foreach($objects as $name => $object) {
            $path = MODULES_PATH.$identifier."/".substr($name, $strlenDir);
            if($object->isDir()) {
                if(!file_exists($path)) {
                    mkdir($path);
                }
            } elseif($object->isFile()) {
                file_put_contents($path, file_get_contents($name));
            }
        }
        if(file_exists($directory."/update")) {
            unlink(MODULES_PATH.$identifier."/update");
            $remove = explode("\n", file_get_contents($directory."/update"));
            foreach($remove as $file) {
                $path = MODULES_PATH.$identifier."/".trim($file);
                if(is_dir($path)) {
                    DirectoryRearranger::remove($path);
                } else {
                    unlink($path);
                }
            }
        }
    }
    
}

?>
