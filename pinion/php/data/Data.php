<?php
/**
 * Every module has an instance of this class.
 * With this class you can create and delete storages and rows.
 * You also can set and get options, get attributes of storages and update an
 * row with an event.
 * 
 * PHP version 5.3
 * 
 * @category   data
 * @package    data
 * @subpackage data
 * @author     Friedolin Förder <friedolin.foerder@pinion-cms.org>
 * @license    MIT License
 * @link       http://www.pinion-cms.org
 */


namespace pinion\data;

use \pinion\modules\ModuleManager;
use \pinion\events\Event;


class Data {
    
    /**
     * The name of the current module
     * 
     * @var string $_moduleName 
     */
    protected $_moduleName;
    
    /**
     * The DataManager of this instance
     * 
     * @var DataManager $_dataManager 
     */
    protected $_dataManager;
    
    /**
     * The current context of this class
     * 
     * @var string $context 
     */
    public $context;
    
    /**
     * The constructor of Data
     * 
     * @param string      $moduleName  The name of the current module
     * @param DataManager $dataManager The DataManager
     */
    public function __construct($moduleName, $dataManager) {
        $this->_moduleName = $moduleName;
        $this->_dataManager = $dataManager;
    }
    
    /**
     *
     * @param string|array $data   A string of a storage in the current module or an array with ActiveRecords
     * @param array        $filter An filter, which determines what attributes will be returned
     * 
     * @return array An multidimensional array with data rows, which have some filtered attributes 
     */
    public function getAttributes($data, array $filter = array()) {
        if(is_string($data)) {
            $data = $this->all($data);
        }
        $attributes = array();
        if(is_array($data)) {
            foreach($data as $activeRecord) {
                $attributes[] = $activeRecord->attributes($filter);
            }
        }
        
        return $attributes;
    }
    
    /**
     * The path where all the files of the current module are stored
     * 
     * @return string The path of the files 
     */
    public function filesPath() {
        return APPLICATION_PATH."files/modules/".$this->_moduleName."/";
    }
    
    /**
     * The url where all the files of the current module are stored
     * 
     * @return string The url of the files 
     */
    public function filesUrl() {
        return SITE_URL."files/modules/".$this->_moduleName."/";
    }
    
    /**
     * Magic method to get a storage by name
     * 
     * @param string $name The name of the storage
     * 
     * @return string The class name with full namespace 
     */
    public function __get($name) {
        return $this->getStorage($name);
    }
    
    /**
     * Get the salt
     * 
     * @return type 
     */
    public function getSalt() {
        return $this->_dataManager->getSalt();
    }
    
    /**
     * Magic method to call the methods of the storage
     * 
     * @param string $name      The name of the method to call
     * @param mixed  $arguments The arguments for the method to call
     * 
     * @return mixed The result of the called method 
     */
    public function __call($name, $arguments) {
        if($name === "create") {
            $this->_dataManager->updateClasses();
        }
        
        $dataName = array_shift($arguments);
        $class = $this->getStorage($dataName);
        
        return call_user_func_array(array($class, $name), $arguments);
    }
    
    /**
     * Get a storage by name
     * 
     * @param string $name The name of the storage
     * 
     * @return string The class name with full namespace 
     */
    public function getStorage($name) {
        return $this->_dataManager->getDataStorage($this->_moduleName."_".$name);
    }
    
    /**
     * Create a storage
     * 
     * @param string $name    The name of the storage to create
     * @param array  $data    The columns of the storage
     * @param array  $options Options for the storage
     * 
     * @return \pinion\data\Data Returns this object
     */
    public function createDataStorage($name, array $data = array(), array $options = array()) {
        $this->_dataManager->createDataStorage($this->_moduleName."_".$name, $data, $options, $this->_moduleName);
                
        return $this;
    }
    
    /**
     * Update a ActiveRecord with an event
     * 
     * @param string $name  The name of the storage to update
     * @param Event  $event The event
     * @return boolean True if the ActiveRecord was updated, false otherwise
     */
    public function updateWithEvent($name, Event $event) {
        $infos = $event->getInfo();
        if(isset($infos["id"])) {
            return false;
        } else {
            $data = $this->find($name, $infos["id"]);
            if(is_null($data)) {
                return false;
            } else {
                foreach($infos as $key => $info) {
                    if($key == "id" || $key == "sender") continue;
                    
                    $data->{$key} = $info[$key];
                }
            }
            $data->save();
            return true;
        }
    }
    
    /**
     * Get or set options
     * 
     * @param string|null $key   The key of the option
     * @param null|array  $value optional if you want set a option, add a value 
     * @param boolean     $new   set this parameter to true, if its a new one
     * 
     * @example if you want to get all options:       [call this function with no parameter]
     * @example if you want get an option (getter):    "numberOfPosts"
     * @example if you want get many options (getter): array("numberOfPosts", "delay")
     * @example if you want create an option (setter): "numberOfPosts", 20, true
     * @example if you want update an option (setter): "numberOfPosts", 20
     * @example if you want to update many options:    null, array("numberOfPosts" => 20, "delay" => 150)
     * 
     * @return mixed In case of a setter you get this object, else you will get an array or the value of the option  
     */
    public function options($key = null, $value = null, $new = false) {
        $backendModule = ModuleManager::getInstance()->module("backend");
        if(is_null($backendModule)) {
            throw new \Exception("The module 'backend' doesn't exist!");
        }
        $data = $backendModule->data;
        
        $numArgs = func_num_args();
        
        if($numArgs == 0) {
            return $this->getAllOptions($data);
        } elseif($numArgs == 1) {
            if(is_string($key)) {
                return $this->getOption($key, $data);
            } elseif(is_array($key)) {
                return $this->getOptions($key, $data);
            }
        } else {
            if(is_string($key)) {
                $this->setOption($key, $value, $new, $data);
                return $this;
            } elseif(is_array($value)) {
                $this->setOptions($value, $new, $data);
                return $this;
            }
        }
        return $this;
    }
    
    /**
     * Get an option of a module
     * 
     * @param string       $key  The key of the option
     * @param ActiveRecord $data The data of the ActiveRecord, which holds all the options
     * 
     * @return mixed The value of the option 
     */
    protected function getOption($key, $data) {
        $option = $data->find_by_module_and_key("option", $this->_moduleName, $key);
        $value = json_decode($option->value);
        return $value[0];
    }
    
    /**
     * Get all options of a module
     * 
     * @param ActiveRecord $data The data of the ActiveRecord, which holds all the options
     * 
     * @return array An array with all options of the module 
     */
    protected function getAllOptions($data) {
        $options = $data->find_all_by_module("option", $this->_moduleName);
        $optionData = array();
        foreach($options as $option) {
            $value = json_decode($option->value);
            $optionData[$option->key] = $value[0];
        }
        return $optionData;
    }
    
    /**
     * Get options of a module
     * 
     * @param array        $keys The options, which should be returned 
     * @param ActiveRecord $data The data of the ActiveRecord, which holds all the options
     * 
     * @return array An array with the requested options 
     */
    protected function getOptions(array $keys, $data) {
        $options = $data->all("option", array("conditions" => array("module = ? AND key IN ?", $this->_moduleName, $keys)));
        $optionData = array();
        foreach($options as $option) {
            $value = json_decode($option->value);
            $optionData[$option->key] = $value[0];
        }
        return $optionData;
    }
    
    /**
     * Set an option
     * 
     * @param string       $key   The key of the option
     * @param mixed        $value The value of the option
     * @param boolean      $new   Set to true if it's a new option and you want to create it
     * @param ActiveRecord $data  The data of the ActiveRecord, which holds all the options
     * 
     * @return \pinion\data\Data Returns this object
     */
    protected function setOption($key, $value, $new, $data) {
        if($new) {
            $data->create("option", array(
                "module" => $this->_moduleName,
                "key"    => $key,
                "value"  => json_encode(array($value))
            ));
        } else {
            $option = $data->find_by_module_and_key("option", $this->_moduleName, $key);
            $option->value = json_encode(array($value));
            $option->save();
        }
        return $this;
    }
    
    /**
     * Set many options with an array
     * 
     * @param array        $values The options
     * @param boolean      $new    Set to true if it's a new option and you want to create it
     * @param ActiveRecord $data   The data of the ActiveRecord, which holds all the options
     * 
     * @return \pinion\data\Data Returns this object
     */
    protected function setOptions(array $values, $new, $data) {
        foreach($values as $key => $value) {
            $this->setOption($key, $value, $new, $data);
        }
        return $this;
    }
    
    /**
     * Delete the data row and the belonging content of an ActiveRecord
     * 
     * @param int     $id   The id of the data row of the ActiveRecord
     * @param boolean $full Set to true if you want to delete it completely
     * 
     * @return \pinion\data\Data Returns this object
     */
    public function deleteData($id, $full = false) {
        $data = $this->find($this->_moduleName, $id);
        $data->delete($full);
        
        $contentData = ModuleManager::getInstance()->module("page")->data->all("content", array("conditions" => array("module = ? AND moduleid = ? AND (deleted = 0 OR deleted = 1)", $this->_moduleName, $id)));
        foreach($contentData as $content) {
            $content->delete($full);
        }
        return $this;
    }
}

?>
