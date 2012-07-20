<?php
/**
 * This class will be used with objects, which should represent the translations
 * of an translated database column.
 * You can iterate over the translation and you cant print this object and 
 * you will get the translation, which match the current language.
 * 
 * PHP version 5.3
 * 
 * @category   data
 * @package    data
 * @subpackage database
 * @author     Friedolin Förder <friedolin.foerder@pinion-cms.org>
 * @license    MIT License
 * @link       http://www.pinion-cms.org
 */


namespace pinion\data\database;

class TranslationObject implements \Iterator {
    
    /**
     * An array with translation information
     * 
     * @var array $data 
     */
    protected $data;
    
    /**
     * An array with translations
     * 
     * @var array $translation 
     */
    public $translations = array();
    
    /**
     * The translation of the current language of the user
     * 
     * @var type 
     */
    public $current;

    /**
     * The constructor of TranslationObject
     * 
     * @param array $array An array with translation information
     */
    public function __construct(array $array) {
        $this->data = $array;
        foreach($array as $language => $row) {
            $this->translations[$language] = $row->translation;
        }
        
        $language = $_SESSION["lang"];
        
        if(isset($this->translations[$language])) {
            $this->current = $this->translations[$language];
        } else {
            $this->current = current($this->translations);
        }
    }
    
    /**
     * Get the translation information
     * 
     * @return array An array with information about the translations
     */
    public function getData() {
        return $this->data;
    }
    
    /**
     * The magic method __toString
     * 
     * @return string Returns the current translation
     */
    public function __toString() {
        return $this->current;
    }

    /**
     * the rewind method is part of the Iterator implementation 
     */
    public function rewind() {
        reset($this->translations);
    }
  
    /**
     * the current method is part of the Iterator implementation 
     */
    public function current() {
        $var = current($this->translations);
        return $var;
    }
  
    /**
     * the key method is part of the Iterator implementation 
     */
    public function key() {
        $var = key($this->translations);
        return $var;
    }
  
    /**
     * the next method is part of the Iterator implementation 
     */
    public function next() {
        $var = next($this->translations);
        return $var;
    }
    
    /**
     * the valid method is part of the Iterator implementation 
     */
    public function valid() {
        $key = key($this->translations);
        $var = ($key !== null && $key !== false);
        return $var;
    }
}

?>
