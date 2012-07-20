<?php
/**
 * The ActiveRecord has got the whole save and update functionality.
 * With this class the models can save their data into the database.
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
use \pinion\general\Registry;
use \pinion\modules\ModuleManager;
use \Closure;
use \pinion\events\EventDispatcher;

class ActiveRecord extends ActiveRecordCache {
    
    /**
     * Determine, if the ActiveRecord could save
     * 
     * @var boolean $doSave 
     */
    public static $doSave = true;
    
    /**
     *
     * @var EventDispatcher $eventObject
     */
    public static $eventObject;
    
    /**
     * The id of the last saved ActiveRecord
     * 
     * @var int $last_id 
     */
    public static $last_id;
    
    /**
     * Determine, if there is a change
     * 
     * @var boolean $noChange
     */
    public static $noChange = false;
    
    /**
     * Has this ActiveRecord already created an revision
     * 
     * @var boolean $_revisionCreated
     */
    protected $_revisionCreated = false;
    
    /**
     * The module, which belongs to this ActiveRecord
     * 
     * @var \pinion\modules\Module $_module 
     */
    protected $_module = null;
    
    /**
     * Checks, if the ActiveRecord supports revisions
     * 
     * @return boolean True, if this ActiveRecord supports revisions, false otherwise 
     */
    public static function hasRevisions() {
        return (isset(static::$belongs_to[0]) && isset(static::$belongs_to[0][0]) && static::$belongs_to[0][0] == "instance");
    }

    /**
     * Save the ActiveRecord
     * 
     * @param boolean $validate Set to true if you want to validate the ActiveRecord
     * @param boolean $noChange Revisions will only created, if noChange is false
     * 
     * @return null 
     */
    public function save($validate = true, $noChange = false) {
        if(!static::$doSave) return;
        
        $new = $this->is_new_record();
        
        // check, if this data has revisions and is a instance (and not only a revision)
        $isInstanceAndHasRevisions = (static::hasRevisions() && is_null($this->instance_id));
        
        if($this->is_dirty()) {
            $time = \time();
            
            
            $this->__set("updated", $time);
            
            if($new) {
                // if the field created is old or the field is already filled with data, skip this step
                if(is_null($this->created)) {
                    $this->__set("created", $time);
                }
                // the record is new -> set user id
                $this->__set("user_id", Registry::getUserId());
            }
        }
        
        if(!$noChange && !static::$noChange) {
            self::dispatchEvent("change", array(
                "data" => $this
            ));
        }
        
        if($isInstanceAndHasRevisions) {
            $revision = Registry::getHeadRevision();
            
            $this->__set("revision", $revision);
            if($new) {
                $this->__set("deleted", false);
            }
        }
        
        parent::save($validate);
        
        if($isInstanceAndHasRevisions && Registry::newRevisionsAllowed()) {
            $this->createRevision();
        }
        
        if($new) {
            static::$last_id = (int)$this->id;
        }
    }
    
    /**
     * Save the ActiveRecord, also if there are no changes 
     */
    public function forceSave() {
        $dirty = $this->is_dirty();
        if(!$dirty) {
            Registry::stopRevisioning();
        }
        
        $this->updated = 1;
        $this->save();
        
        if(!$dirty) {
            Registry::startRevisioning();
        }
    }
    
    /**
     * Create a revision 
     */
    protected function createRevision() {
        if($this->_revisionCreated) {
            // there is already a revision 
            $activeRecord = static::find(array("conditions" => array("instance_id = ? AND revision = ?", $this->id, $this->revision)));
        } else {
            // create a new revision
            $class = get_called_class();
            $activeRecord = new $class();
        }
        
        // copy attributes
        $attributes = $this->attributes();
        foreach($attributes as $name => $value) {
            // only copy "normal" attributes
            if($name == "id" || $name == "instance_id" || $name == "created" || $name == "updated") {
                continue;
            }
            // copy one attribute
            $activeRecord->__set($name, $value);
        }
        // set instance
        $activeRecord->__set("instance_id", $this->id);
        $activeRecord->__set("created", $this->updated);
        $activeRecord->__set("updated", $this->updated);
        
        // save active record
        $activeRecord->save();
        
        // set a flag
        $this->_revisionCreated = true;
    }
    
    /**
     * Copy the ActiveRecord
     * 
     * @return ActiveRecord The copy of this ActiveRecord
     */
    public function copy() {
        $class = get_called_class();
        $activeRecord = new $class();
        
        // copy attributes
        $attributes = $this->attributes();
        foreach($attributes as $name => $value) {
            if($name == "id" || $name == "instance_id" || $name == "created" || $name == "updated") {
                continue;
            }
            
            $activeRecord->__set($name, $value);
        }
        $activeRecord->__set("created", $this->updated);
        $activeRecord->__set("updated", $this->updated);
        
        return $activeRecord;
    }
    
    /**
     * Extract and validate the options of an find request
     * 
     * @param array $array The options
     * 
     * @return array An array of extracted and validated options
     */
    public static function extract_and_validate_options(array &$array) {
        
        $options = parent::extract_and_validate_options($array);
        
        $findById = false;
        if(count($array) == 1 && is_numeric($array[0]) || isset($options["conditions"]) && preg_match("/^\s*?id\s*?=/", $options["conditions"][0])) {
            $findById = true;
        }
        
        if(!$findById) {
            if(static::hasRevisions()) {
                $instanceCondition = "instance_id IS NULL";
                $deletedCondition = "deleted = 0";

                if(isset($options["conditions"])) {
                    $instanceInCondition = preg_match("/instance_id/", $options["conditions"][0]);
                    $deletedInCondition = preg_match("/deleted\s*?=\s*?/", $options["conditions"][0]);

                    if(!$instanceInCondition) {
                        $options["conditions"][0] .= " AND $instanceCondition";
                    }
                    if(!$deletedInCondition) {
                        $options["conditions"][0] .= " AND $deletedCondition";
                    }
                } else {
                    $options["conditions"] = array($instanceCondition." AND ".$deletedCondition);
                }
            }
            if(static::$hasPosition && !array_key_exists("order", $options)) {
                $options["order"] = "position asc";
            }
        }
        
        return $options;
    }
    
    /**
     * Assign an attribute
     * 
     * @param string $name  The name of the attribute
     * @param mixed  $value The value of the attribute
     * 
     * @return mixed The value
     */
    public function assign_attribute($name, $value) {
        
        // if the value is not the same as now, set it to dirty and assign the value
        if(!$this->is_new_record() && $this->__isset($name) && $value == $this->__get($name)) {
            return $value;
        } else {
            return parent::assign_attribute($name, $value);
        }
        
    }
    
    /**
     * Set an attribute
     * 
     * @param string $name  The name of the attribute
     * @param mixed  $value The value of the attribute
     * 
     * @return null 
     */
    public function __set($name, $value) {
        
        if(isset($this->{$name."_translationid"}) && (!is_null($this->{$name."_translationid"}) || !is_string($value))) {
            
            if(is_array($value)) {
                // ok
            } elseif($value instanceof TranslationObject) {
                $value = $value->translations;
            } else {
                return parent::__set($name, $value);
            }
            
            $translationId = $this->{$name."_translationid"};
            $newTranslationId = ModuleManager::getInstance()->module("translation")->setFrontendTranslation($translationId, $value);
            
            if(is_null($translationId)) {
                if(!array_key_exists(Registry::getLanguage(), $value)) {
                    $value[Registry::getLanguage()] = $this->__get($name);
                }
                $this->__set($name."_translationid", $newTranslationId);
            }
            $value = null;
        }
        
        return parent::__set($name, $value);
    }
    
    /**
     * Get an attribute
     * 
     * @param type $name The name of the attribute
     * 
     * @return mixed The value of the attribute 
     */
    public function &__get($name) {
        
        if(isset($this->{$name."_translationid"}) && !is_null($this->{$name."_translationid"})) {
            $translationId = $this->{$name."_translationid"};
            
            
            $array = ModuleManager::getInstance()->module("translation")->data->all("frontend", array("conditions" => array("frontend_id = ?", $translationId)));
            $dataArray = array();
            foreach($array as $row) {
                $dataArray[$row->language] = $row;
            }
            $translationObject = new TranslationObject($dataArray);
            return $translationObject;
        }
        
        return parent::__get($name);
    }
    
    /**
     * Constructs a ActiveRecord
     * 
     * @param array   $attributes             The attributes of the ActiveRecord
     * @param boolean $guard_attributes       Set to true to guard protected/non-accessible attributes
     * @param boolean $instantiating_via_find Set to true if the ActiveRecord is being created from a find call
     * @param type    $new_record             Set to true if this should be considered a new record
     * 
     * @return ActiveRecord The constructed ActiveRecord
     */
    public function __construct(array $attributes = array(), $guard_attributes = true, $instantiating_via_find = false, $new_record = true) {
        
        parent::__construct($attributes, $guard_attributes, $instantiating_via_find, $new_record);
        
        $this->setTranslationArrays($attributes);
    }
    
    /**
     * Set attributes of the ActiveRecord
     * 
     * @param array $attributes The attributes of the ActiveRecord
     */
    public function set_attributes(array $attributes) {
        parent::set_attributes($attributes);
        
        $this->setTranslationArrays($attributes);
    }
    
    /**
     * Set the translation with the attributes of the ActiveRecord
     * 
     * @param array $attributes The attributes of the ActiveRecord
     */
    private function setTranslationArrays(array $attributes) {
        foreach($attributes as $name => $value) {
            if(isset($this->{$name."_translationid"}) && is_array($value)) {
                $this->__set($name, $value);
            }
        }
    }
    
    /**
     * Get and filter your attributes
     * 
     * @param array $filter An filter, which determines what attributes will be returned
     * 
     * @return array An array with filtered attributes 
     */
    public function attributes(array $filter = array()) {
        
        
        
        if(!empty($filter)) {
            $filteredAttributes = array();
            $allAttributes = false;
            $closureForAll = null;
            if(array_key_exists("*", $filter)) {
                $allAttributes = true;
                if($filter["*"] instanceof Closure) {
                    $closureForAll = $filter["*"];
                }
            } elseif(array_search("*", $filter) !== false) {
                $allAttributes = true;
            }
            
            
            
            foreach($filter as $key => $value) {
                if($key === "*" || $value === "*") continue;
                
                $closure = $closureForAll;
                if(is_string($value)) {
                    $column = $value;
                } else {
                    $column = $key;
                    if($value instanceof Closure) {
                        $closure = $value;
                    }
                }
                
                if(isset($this->{$column})) {
                    if($closure && $column !== "id") {
                        $columnValue = $closure($this->{$column});
                    } else {
                        $columnValue = $this->{$column};
                    }
                    
                    $filteredAttributes[$column] = $columnValue;
                } elseif($this->table()->has_relationship($column)) {
                    $activeRecord = $this->{$column};
                    if($activeRecord instanceof ActiveRecord) { // belongs to relationship
                        if(is_array($value)) {
                            $columnValue = $activeRecord->attributes($value);
                        } elseif($value instanceof Closure) {
                            $columnValue = $value($activeRecord);
                        } else {
                            $columnValue = $activeRecord->attributes();
                        }
                    } elseif(is_array($activeRecord)) { // has many relationship
                        $columnValue = array();
                        if(is_array($value)) {
                            foreach($activeRecord as $ar) {
                                $columnValue[] = $ar->attributes($value);
                            }
                        } elseif($value instanceof Closure) {
                            foreach($activeRecord as $ar) {
                                $columnValue[] = $value($ar);
                            }
                        } else {
                            foreach($activeRecord as $ar) {
                                $columnValue[] = $ar->attributes();
                            }
                        }
                    } else {
                        $columnValue = $activeRecord;
                    }
                    
                    $filteredAttributes[$column] = $columnValue;
                } elseif($value instanceof Closure) {
                    // you can add non existing values
                    $columnValue = $value($this);
                    
                    $filteredAttributes[$column] = $columnValue;
                } else {
                    $filteredAttributes[$key] = $value;
                }
            }
            
            // if all attributes are requested, get all attributes and merge them
            // with the filtered attributes
            if($allAttributes) {
                $attributes = parent::attributes();
                if($closureForAll) {
                    foreach($attributes as $key => $value) {
                        if($key !== "id" && !array_key_exists($key, $filteredAttributes)) {
                            $filteredAttributes[$key] = $closureForAll($value);
                        }
                    }
                } else {
                    $filteredAttributes = array_merge($attributes, $filteredAttributes);
                }
            }
            
            // if id is not in the filter, add it to the filtered attributes
            if(!array_key_exists("id", $filteredAttributes)) {
                $filteredAttributes["id"] = $this->id;
            }
            
            return $filteredAttributes;
        }
        
        return parent::attributes();
    }
    
    /**
     * Delete the row of the ActiveRecord
     * 
     * @param boolean $full Set to true if the row of the ActiveRecord should be completely deleted
     */
    public function delete($full = false) {
        if($full || !$this->hasRevisions()) {
            parent::delete();
        } else {
            $this->deleted = true;
            $this->save();
            if(isset(static::$has_many)) {
                foreach(static::$has_many as $relationship) {
                    $relation = $relationship[0];
                    if($relation === "revisions") continue;
                    
                    $relations = $this->{$relation};
                    foreach($relations as $oneRelation) {
                        $oneRelation->delete();
                    }
                }
            }
            
        }
        
    }
    
    /**
     * Revert the row of the ActiveRecord to an older version
     * 
     * @param int $revision The number of the version
     */
    public function revert($revision) {
        // get the revision
        $newRevision = static::first(array("conditions" => array("revision <= ? AND instance_id = ? AND (deleted = 0 OR deleted = 1)", $revision, $this->id), "order" => "revision desc"));
        
        if(!is_object($newRevision)) {
            // if there is not an so old revision, get the oldest revision
            $newRevision = static::first(array("conditions" => array("instance_id = ? AND (deleted = 0 OR deleted = 1)", $this->id), "order" => "revision asc"));
        }
        
        if(is_object($newRevision)) {
            
            // revert (copy attributes)
            $attributes = $newRevision->attributes();
            foreach($attributes as $name => $value) {
                if($name == "id" || $name == "instance_id" || $name == "created" || $name == "updated") {
                    continue;
                }
                if(substr($name, -14) == "_translationid" && !is_null($value)) {
                    $translationData = $this->__get(substr($name, 0, -14))->getData();
                    foreach($translationData as $language => $translation) {
                        $translation->revert($revision);
                    }
                }
                $this->__set($name, $value);
                $this->save();
            }
        }
        
    }
    
    /**
     * Reload the ActiveRecord, so it has the current attributes from the database
     * 
     * @return ActiveRecord The current ActiveRecord
     */
    public function reload() {
        $this->resetRelationships();
        $this->set_attributes($this->find_uncached($this->id)->attributes(), false);
        $this->reset_dirty();
        return $this;
    }
    
    /**
     * Massive Update of rows
     * 
     * @param string|array $data  The data, which should be used for the update
     * @param string|array $where The condition, which should be used for the update
     * 
     * @return \PDOStatement The statement of the query 
     */
    public static function massUpdate($data, $where) {
        
        $table = self::table();
        
        if(is_array($data)) {
            return $table->update($data, $where);
        }
        $sql = new \ActiveRecord\SQLBuilder($table->conn,$table->get_fully_qualified_table_name());
        $sql->update($data)->where($where);

        $values = $sql->bind_values();
        return $table->conn->query(($table->last_sql = $sql->to_s()),$values);
    }
    
    /**
     * Massive Deletion of rows
     * 
     * @param string|array $where The condition, which should be used for the deletion
     * 
     * @return \PDOStatement The statement of the query 
     */
    public static function massDelete($where) {
        
        $table = self::table();
        
        if(is_array($where)) {
            return $table->update($where);
        }
        $sql = new \ActiveRecord\SQLBuilder($table->conn,$table->get_fully_qualified_table_name());
        $sql->delete($where);

        $values = $sql->bind_values();
        return $table->conn->query(($table->last_sql = $sql->to_s()),$values);
    }
    
    /**
     * Update the attributes of the ActiveRecord
     * 
     * @param array $attributes An array with attributes for the ActiveRecord
     * 
     * @return boolean True, if the ActiveRecord was updated, false otherwise 
     */
    public function update_attributes($attributes) {
        $table = static::table();
        
        // you can only delete a file with the delete function
        if(isset($attributes["id"])) {
            unset($attributes["id"]);
        }
        if(isset($attributes["deleted"])) {
            unset($attributes["deleted"]);
        }
        // you can't change the user id
        if(isset($attributes["instance_id"])) {
            unset($attributes["instance_id"]);
        }
        // you can't change the revision
        if(isset($attributes["revision"])) {
            unset($attributes["user_id"]);
        }
        // you can't change the user id
        if(isset($attributes["user_id"])) {
            unset($attributes["user_id"]);
        }
        // you can't change the user id
        if(isset($attributes["created"])) {
            unset($attributes["created"]);
        }
        // you can't change the user id
        if(isset($attributes["updated"])) {
            unset($attributes["updated"]);
        }
        
        foreach($attributes as $name => $attribute) {
            if(!array_key_exists($name, $table->columns) || $name == "deleted" || $name == "instance_id" || $name == "revision" || $name == "user_id") {
                unset($attributes[$name]);
            }
        }
        return parent::update_attributes($attributes);
    }
    
    /**
     * Get the module, which belongs to the ActiveRecord
     * 
     * @return \pinion\modules\Module The module, which belongs to the ActiveRecord 
     */
    public function module() {
        if(!isset($this->_module)) {
            $module = explode("_", $this::$table_name);
            $module = end($module);
            $this->_module = ModuleManager::getInstance()->module($module);
        }
        return $this->_module;
    }

    /**
     * Add an EventListener to the EventDispatcher of the ActiveRecord
     * 
     * @param string         $eventName           The name of the event
     * @param Closure|string $closureOrMethodname A closure or a string with a name of a method
     * 
     * @return EventDispatcher The EventDispatcher of this ActiveRecord
     */
    public static function addEventListener($eventName, $closureOrMethodname = null) {
        return call_user_func_array(array(self::$eventObject, "addEventListener"), func_get_args());
    }

    /**
     * Dispatch an event for the EventDispatcher of the ActiveRecord
     * 
     * @param string|\pinion\events\Event $event   The name of the event or an event object
     * @param array                       $info    An array with event information
     * @param Object                      $context The current context object
     * 
     * @return EventDispatcher The EventDispatcher of this ActiveRecord
     */
    public static function dispatchEvent($event, $info = null, $context = null) {
        return self::$eventObject->dispatchEvent($event, $info, $context);
    }

    /**
     * Remove an EventListener of the EventDispatcher of the ActiveRecord
     * 
     * @param string         $eventName           The name of the event
     * @param Closure|string $closureOrMethodname A closure or a string with a name of a method
     * 
     * @return EventDispatcher The EventDispatcher of this ActiveRecord
     */
    public static function removeEventListener($eventName, $closureOrMethodname) {
        return call_user_func_array(array(self::$eventObject, "removeEventListener"), func_get_args());
    }
    
    /**
     * Checks, if the ActiveRecord contains to the current user
     * 
     * @return boolean True, if the ActiveRecord contains to the current user, false otherwise
     */
    public function isOwn() {
        $identity = \Zend_Auth::getInstance()->getIdentity();
        if($identity && isset($this->user_id) && $this->user_id == $identity->userid) {
            return true;
        } else {
            return false;
        }
    }
}

?>
