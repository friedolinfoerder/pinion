<?php
/**
 * Simple wrapper of class FirePHP
 *
 * @author Friedolin
 */
class Console {
    
    protected static $_inizialized = false;
    
    public static function firebug() {
        if(!defined("DEBUG") || !DEBUG) return false;
        if(!self::$_inizialized) {
            require_once 'FirePHPCore/fb.php';
        }
    }
    
    public static function log($object, $label = null) {
        if(!defined("DEBUG") || !DEBUG) return false;
        self::firebug();
        return FB::log($object, $label);
    }
    
    public static function __callStatic($name, $arguments) {
        if(!defined("DEBUG") || !DEBUG) return false;
        self::firebug();
        return call_user_func_array(array("FB", $name), $arguments);
    }
}
?>
