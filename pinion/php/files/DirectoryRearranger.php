<?php
/**
 * This class manages all interactions with directories.
 * It can copy, clear, delete, move, zip directories and can unzip zip-files.
 * 
 * PHP version 5.3
 * 
 * @category   files
 * @package    files
 * @subpackage files
 * @author     Friedolin Förder <friedolin.foerder@pinion-cms.org>
 * @license    MIT License
 * @link       http://www.pinion-cms.org
 */


namespace pinion\files;


class DirectoryRearranger {
    
    /**
     * Copy a directory to a new place
     * 
     * @param string $source      The path to the source directory
     * @param string $destination The path to the destination directory
     */
    public static function copy($source, $destination) {
        
        $source = pathinfo($source);
        $destination = pathinfo($destination);
        
        $oldDir = $source["dirname"]."/".$source["basename"];
        $newDir = $destination["dirname"]."/".$destination["basename"];
        
        $dirIterator = new \RecursiveDirectoryIterator($oldDir);

        \mkdir($newDir);

        $objects = new \RecursiveIteratorIterator($dirIterator, \RecursiveIteratorIterator::SELF_FIRST);
        foreach($objects as $name => $object) {
            $tpath = substr_replace($name, $newDir, 0, strlen($oldDir));
            if ($object->isDir()) {
                \mkdir($tpath);
            }
            else if ($object->isFile()) {
                \copy($name, $tpath);
            }
        }
    }
    
    /**
     * Removes a directory, if it's not already removed
     * 
     * @param string $directory The path to the directory
     */
    public static function remove($directory) {
        if(file_exists($directory)) {
            self::clear($directory);
            \rmdir($directory);
        }
    }
    
    /**
     * Creates a directory, if it's not already created
     * 
     * @param string $directory The path to the new directory
     */
    public static function create($directory) {
        if(!file_exists($directory)) {
            mkdir($directory);
        }
    }
    
    /**
     * Clears a directory
     * 
     * @param string $directory The path to the directory
     */
    public static function clear($directory) {
        $dirIterator = new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS);

        $objects = new \RecursiveIteratorIterator($dirIterator, \RecursiveIteratorIterator::CHILD_FIRST);
        foreach($objects as $name => $object) {
            if($object->isDir()) {
                \rmdir($name);
            } elseif($object->isFile()) {
                \unlink($name);
            }
        }
    }

    /**
     * Moves a directory to a new destination
     * 
     * @param string $source      The path to the source directory
     * @param string $destination The path to the destination directory
     */
    public static function move($source, $destination) {
        self::copy($source, $destination);
        self::remove($source);
    }

    /**
     * Creates a zip file
     * 
     * @param string $source      The path to the source directory
     * @param string $destination The path to the destination zip file
     * 
     * @return boolean Returns true, if the zip file was created, otherwise false
     */
    public static function zip($source, $destination) {
        if(is_file($destination)) return false;

        $zipArchive = new ZipArchive();
        
        if($zipArchive->open($destination, ZipArchive::CREATE) !== true) return false;

        $dirIterator = new \RecursiveDirectoryIterator($source);

        $objects = new \RecursiveIteratorIterator($dirIterator, \RecursiveIteratorIterator::SELF_FIRST);
        foreach($objects as $name => $object){
            if ($object->isFile()) {
                $zipArchive->addFile($name, substr_replace($name, "", 0, strlen($source)+1));
            }
        }

        $zipArchive->close();
        return true;
    }

    /**
     * Unzip a file
     * 
     * @param string $zipPath         The path to the zip file
     * @param string $destinationPath optional The path to the destination directory
     */
    public static function unzip($zipPath, $destinationPath = null) {
        if(is_null($destinationPath)) {
            $destinationPath = substr($zipPath, 0, -4);
        }
        $zipArchive = new \ZipArchive();

        $zipArchive->open($zipPath);
        $zipArchive->extractTo($destinationPath);
        $zipArchive->close();
    }

}
?>
