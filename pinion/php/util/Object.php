<?php
/**
 * Class Object
 * 
 * PHP version 5.3
 * 
 * @category   util
 * @package    util
 * @subpackage util
 * @author     Friedolin Förder <friedolin.foerder@pinion-cms.org>
 * @license    MIT License
 * @link       http://www.pinion-cms.org
 */
/**
 * This class has useful methods for changing objects or arrays.
**/

namespace pinion\util;

class Object {
    
    /**
     * A function to make a deep merge of two arrays
     * 
     * @param array $base   The array to extend
     * @param array $extend The array which extends the first
     * 
     * @return array The merged array 
     */
    public static function merge(array $base, array $extend) {
        foreach($extend as $key => $value) {
            if(array_key_exists($key, $base) && is_array($value)) {
                $base[$key] = self::merge($base[$key], $extend[$key]);
            } else {
                $base[$key] = $value;
            }
        }
        
        return $base;
    }
    
}
?>
