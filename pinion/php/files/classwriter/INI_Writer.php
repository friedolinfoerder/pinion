<?php
/**
 * Class INI_Writer
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

class INI_Writer {
    
    /**
     * Write an ini file with an array
     * 
     * @param array   $array    An array with data to write
     * @param string  $path     The path to the ini file
     * @param boolean $sections Set to true if you want to use ini sections 
     */
    public static function writeWithArray(array $array, $path, $sections = false) {
        $data = "";
        foreach($array as $key => $value) {
            if($sections) {
                $data .= "[$key]\n";
                foreach($value as $k => $v) {
                    static::_writeOne($k, $v, $data);
                }
            } else {
                static::_writeOne($key, $value, $data);
            }
        }
        file_put_contents($path, $data);
    }
    
    /**
     * Write one line of an ini file
     * 
     * @param string $key   The key of the attribute
     * @param mixed  $value The value of the attribute
     * @param string $data  The current ini content
     */
    protected static function _writeOne($key, $value, &$data) {
        if(is_array($value)) {
            foreach($value as $k => $v) {
                $data .= "{$key}[$k] = \"$v\"\n";
            }
        } elseif(is_bool($value)) {
            $data .= "$key = ".($value ? "1" : "0")."\n";
        } else {
            $data .= "$key = \"$value\"\n";
        }
    }
}

?>
