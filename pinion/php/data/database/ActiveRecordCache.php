<?php
/**
 * This class has got simple cache functionality for ActiveRecords.
 * If an ActiveRecord is requested with an id, this class saves the ActiveRecord
 * and next time this object will be returned.
 * It decreases the number of queries. 
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

use \ActiveRecord\Model;

class ActiveRecordCache extends Model {
    
    /**
     * The array with cached ActiveRecords
     * 
     * @var array $_cache 
     */
    protected static $_cache = array();
    
    /**
     * Find an ActiveRecord without the help of the cache
     * 
     * @param int $id The id of an ActiveRecord
     * 
     * @return ActiveRecord The requested ActiveRecord 
     */
    public static function find_uncached($id) {
        $class = get_called_class();
        
        if(!isset(static::$_cache[$class])) {
            static::$_cache[$class] = array();
        }
        
        static::$_cache[$class][$id] = parent::find($id);
        return static::$_cache[$class][$id];
    }
    
    /**
     * Find an ActiveRecord with the help of the cache
     * 
     * @return ActiveRecord The requested ActiveRecord 
     */
    public static function find() {
        $class = get_called_class();
        $args = func_get_args();
        if(count($args) == 1 && is_numeric($args[0])) {
            if(!isset(static::$_cache[$class])) {
                static::$_cache[$class] = array();
            }
            
            if(isset(static::$_cache[$class][$args[0]])) {
                return static::$_cache[$class][$args[0]];
            } else {
                $activeRecord = call_user_func_array('parent::find', $args);
                static::$_cache[$class][$activeRecord->id] = $activeRecord;
                return $activeRecord;
            }
        } else {
            return call_user_func_array('parent::find', $args);
        }
    }
    
    /**
     * Save an ActiveRecord and save it to the cached ActiveRecords
     * 
     * @param boolean $validate Set to true if you want to validate the ActiveRecord
     */
    public function save($validate = true) {
        $class = get_called_class();

        parent::save($validate);
        
        if(!isset(static::$_cache[$class])) {
            static::$_cache[$class] = array();
        }
        static::$_cache[$class][$this->id] = $this;
    }
    
    /**
     * Delete an ActiveRecord and delete the ActiveRecord from the cache
     * 
     * @param type $full 
     */
    public function delete($full = false) {
        parent::delete();
        
        if($full) {
            $class = get_called_class();
            if(isset(self::$_cache[$class])) {
                if(isset(self::$_cache[$class][$this->id])) {
                    unset(self::$_cache[$class][$this->id]);
                }
            }
        }
    }
    
    /**
     * Clear the cache of the current class
     * 
     * @return ActiveRecordCache Return the cache class
     */
    public function clearCache() {
        $class = get_called_class();
        
        self::$_cache[$class] = array();
        
        return $this;
    }
}

?>
