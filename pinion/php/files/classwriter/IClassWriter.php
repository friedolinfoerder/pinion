<?php
/**
 * Class IClassWriter
 * 
 * PHP version 5.3
 * 
 * @category   files
 * @package    files
 * @subpackage classwriter
 * @author     Friedolin Förder <friedolin.foerder@pinion-cms.org>
 * @license    MIT License
 * @link       http://www.pinion-cms.org
 */

namespace pinion\files\classwriter;


interface IClassWriter {
    
    /**
     * Set the namespace of the class
     * 
     * @param string $namespace The namespace of the class 
     */
    public function setNamespace($namespace);
    
    /**
     * Set the class
     * 
     * @param string      $classname       The name of the class
     * @param null|string $access          The access type of the class
     * @param null|string $extension       The extended class
     * @param array       $implementations The interface implementations of the class 
     */
    public function setClass($classname, $access = null, $extension = null, array $implementions = array());
    
    /**
     * Add a method
     * 
     * @param string $name    The name of the method
     * @param string $content The content of the method
     * @param string $access  The access type of the method
     */
    public function addMethod($name, $content="", $access="public");
    
    /**
     * Add an attribute
     * 
     * @param string $name   The name of the attribute
     * @param string $access The access type of the attribute
     * @param mixed  $value  The value of the attribute
     */
    public function addAttribute($name, $access="public", $value=null);
    
    /**
     * Save the class
     * 
     * @param string      $directory The directory, in which the class should be created
     * @param null|string $filename  If the classname is not the same as the file name, provide the filename here
     */
    public function save($directory = "", $filename = null);
    
    /**
     * Set the path, in which the class should be saved
     * 
     * @param string $path The path of the file 
     */
    public function setSavePath($path);
}
?>
