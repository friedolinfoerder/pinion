<?php
/**
 * The Autoloader class uses the spl_autoload_register function to create an
 * stack of autoloading functions.
 * 
 * PHP version 5.3
 * 
 * @category   general
 * @package    general
 * @subpackage general
 * @author     Friedolin Förder <friedolin.foerder@pinion-cms.org>
 * @license    MIT License
 * @link       http://www.pinion-cms.org
 */


namespace pinion\general;

class Autoloader {
    
    /**
     * pinion namespace autoloader
     * 
     * @param string $className The name of the class with namespace
     */
    public function pinionAutoload($className) {
        // there is no folder php in the namespace, so add it to the namespace
        $className = str_replace("pinion", "pinion\\php", $className);
        $filePath = str_replace("\\", "/", $className).".php";
        if(file_exists($filePath)) {
            require $filePath;
        }
    }
    
    /**
     * module namespace autoloader
     * 
     * @param string $className The name of the class with namespace
     */
    public function moduleAutoload($className) {
        $classNameParts = explode("\\", $className);
        
        $classNameParts[] = ucfirst(array_pop($classNameParts));
        
        $classNameParts[0] .= "/active";
        
        $filePath = join("/", $classNameParts).".php";
        if(file_exists($filePath)) {
            require $filePath;
        }
    }

    /**
     * zend autoloader
     * 
     * @param string $className The name of the class with namespace
     */
    public function zendAutoload($className) {
        $className = str_replace("_", "/", $className);
        $filePath = "pinion/thirdparty/$className.php";
        if(file_exists($filePath)) {
            require $filePath;
        }
    }

    /**
     * Set autoloader functions 
     * 
     * @param array $functions An array with autoloader functions
     */
    public function setAutoloader(array $functions) {
        foreach($functions as $function) {
            if(is_string($function)) {
                \spl_autoload_register(array($this, $function));
            }
        }
    }

}
?>
