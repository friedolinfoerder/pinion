<?php
/**
 * With this class you can create and delete tables.
 * Also the ActiveRecord classes will be created with this class.
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

use \pinion\files\classwriter\IClassWriter;
use \pinion\data\DataManager;


class DBActiveRecordAdminister extends DBAdminister implements DataManager {
    
    /**
     * An implemention of IClassWriter
     * 
     * @var IClassWriter $_classWriter
     */
    protected $_classWriter;
    
    /**
     * An array with all ActiveRecord classes, which must be updated or created
     * 
     * @var array $_allClasses
     */
    protected $_allClasses = array();
    
    /**
     * An array with all ActiveRecord classes
     * 
     * @var array $_classes 
     */
    protected $_classes = array();
    
    /**
     * The namespace of the ActiveRecords in the model directory
     * 
     * @var string $_modelNamespace 
     */
    protected $_modelNamespace;
    
    /**
     * The path to the model directory
     * 
     * @var string $_modelPath 
     */
    protected $_modelPath;
    
    /**
     * An array with deleted ActiveRecord classes
     * 
     * @var array $_deletedClasses
     */
    protected $_deletedClasses = array();
    
    /**
     * An array with cached data
     * 
     * @var array $_cachedData 
     */
    protected $_cachedData = array();
    
    /**
     * An array with all tables, which will be created
     * 
     * @var array $_tableCreateQueue 
     */
    protected $_tableCreateQueue = array();
    
    /**
     * An array with all tables, which will be deleted
     * 
     * @var array $_tableDeleteQueue 
     */
    protected $_tableDeleteQueue = array();
    
    /**
     * Flag to determine if you can uninstall tables
     * 
     * @var boolean $uninstall 
     */
    public $uninstall = false;

    /**
     * Constructor for Administer
     * 
     * @param IClassWriter $classWriter    An implemention of IClassWriter
     * @param string       $modelPath      The path where the models are located
     * @param string       $modelNamespace The namespace of the models
     * @param string       $file           The file path of the database config
     */
    public function __construct(IClassWriter $classWriter, $modelPath, $modelNamespace, $file) {
        parent::__construct($file);

        $this->_modelNamespace = $modelNamespace;
        $this->_modelPath = $modelPath;

        $this->_classWriter = $classWriter; 
        $this->_classWriter->setSavePath($modelPath);

        $dsn = "{$this->_database["driver"]}://{$this->_database["username"]}:{$this->_database["password"]}@{$this->_database["host"]}/{$this->_database["name"]}?charset=utf8";
        
        require_once 'php-activerecord/ActiveRecord.php';
        $cfg = \ActiveRecord\Config::instance();
        $cfg->set_connections(array('development' => $dsn));
        
        // logging of the database
        $cfg->set_logger(new ConsoleLogger());
        $cfg->set_logging(false);
    }

    /**
     * Method for creating a active record
     * 
     * @param string $tableName The name of the data storage to create
     * @param array  $columns   The columns of the table
     * 
     * @return DBActiveRecordAdminister Fluent interface
     */
    public function createTable($tableName, array $columns = array()) {
        parent::createTable($tableName, $columns);
        
        $class = ucfirst(strtolower($this->_currentTable));
        $this->addClass($class, array_key_exists("position", $columns));

        // fluent interface
        return $this;
    }

    /**
     * Method for set a foreign key
     * 
     * @param string $primaryTable The name of the primary table
     * @param type   $foreignKey   The name of the foreign key
     * @param type   $onDelete     The action, when deleting a row, e.g. cascade
     * @param type   $onUpdate     The action, when updating a row, e.g. cascade
     * 
     * @return DBActiveRecordAdminister 
     */
    public function toForeignKey($primaryTable = null, $foreignKey = null, $onDelete = "CASCADE", $onUpdate = "CASCADE") {
        parent::toForeignKey($primaryTable, $foreignKey, $onDelete, $onUpdate);
        
        if($foreignKey == null) {
            $foreignKey = $this->_currentColumn;
        }

        if($primaryTable == null) {
            $otherTableName = explode("_", $foreignKey);
            $otherTableName = $otherTableName[0];
            $primaryTable = $otherTableName;
        }

        $class = ucfirst(strtolower($this->_currentTable));
        $secondClass = ucfirst(strtolower($primaryTable));

        $this->addClass($class);
        $this->addClass($secondClass);
        
        $column = substr($this->_currentColumn, 0, strlen($this->_currentColumn) - 3);

        if($class == $secondClass) {
            
            $belongsToName = "parent";
            $hasManyName = "children";
            
            
            if($this->_currentColumn == "instance_id") {
                $belongsToName = "instance";
                $hasManyName = "revisions";
            }
            
            
            $belongsTo = array($belongsToName, "class_name" => $this->_modelNamespace.$class, "foreign_key" => $this->_currentColumn);
            $this->_classes[$class]["belongs_to"][] = $belongsTo;
            
            $hasMany = array($hasManyName, "class_name" => $this->_modelNamespace.$class, "foreign_key" => $this->_currentColumn);
            if($hasManyName == "revisions") {
                // if it's a revision relationship sort by revision
                $hasMany["order"] = "revision desc";
            } elseif($this->columnExist($this->_currentTable, "position")) {
                // if there is a column position sort by this column
                $hasMany["order"] = "position asc";
            }
            
            $this->_classes[$class]["has_many"][] = $hasMany;
            
            self::makeArrayUnique($this->_classes[$class]["belongs_to"]);
            self::makeArrayUnique($this->_classes[$class]["has_many"]);
        } else {
            
            $belongsTo = array(substr($foreignKey, 0, -3), "class_name" => $this->_modelNamespace.$secondClass, "foreign_key" => $this->_currentColumn);
            $this->_classes[$class]["belongs_to"][] = $belongsTo;
            
            $primaryColumnName = explode("_", $this->_currentTable);
            array_shift($primaryColumnName);
            $primaryColumnName = join("_", $primaryColumnName);
            
            $hasMany = array($primaryColumnName."s", "class_name" => $this->_modelNamespace.$class, "foreign_key" => $this->_currentColumn);
            
            if($this->columnExist($this->_currentTable, "position")) {
                $hasMany["order"] = "position asc";
            }
            
            $this->_classes[$secondClass]["has_many"][] = $hasMany;
            
            self::makeArrayUnique($this->_classes[$class]["belongs_to"]);
            self::makeArrayUnique($this->_classes[$secondClass]["has_many"]);
        }

        // fluent interface
        return $this;
    }

    /**
     * Create a many to many relationship
     * 
     * @param string $firstColumn  The column of the first table
     * @param string $secondColumn The column of the second table
     * 
     * @return DBActiveRecordAdminister 
     */
    public function manyToMany($firstColumn, $secondColumn) {
        parent::manyToMany($firstColumn, $secondColumn);

        $class  = ucfirst(strtolower($this->_currentTable));
        $secondClass = ucfirst(strtolower(substr($firstColumn, 0, strlen($firstColumn)-3)));
        $thirdClass = ucfirst(strtolower(substr($secondColumn, 0, strlen($secondColumn)-3)));

        $this->addClass($secondClass);
        $this->addClass($thirdClass);

        $this->_classes[$secondClass]["has_many"][] = array($this->_modelNamespace.$thirdClass."s", 'through' => $this->_modelNamespace.\strtolower($class)."s");
        $this->_classes[$thirdClass]["has_many"][] = array($this->_modelNamespace.$secondClass."s", 'through' => $this->_modelNamespace.\strtolower($class)."s");

        self::makeArrayUnique($this->_classes[$secondClass]["has_many"]);
        self::makeArrayUnique($this->_classes[$thirdClass]["has_many"]);

        // fluent interface
        return $this;
    }

    /**
     * Method for removing a table
     * 
     * @param string $name The name of the data storage
     * 
     * @return DBActiveRecordAdminister Fluent interface
     */
    public function removeTable($name) {
        parent::removeTable($name);

        $class = ucfirst($name);
        $classWithNamespace = $this->_modelNamespace.$class;
        $filename = $this->_modelPath.$class.".php";
        
        // remove all relationships in other ActiveRecords
        if(file_exists($filename)) {
            if(property_exists($classWithNamespace, "belongs_to")) {
                $belongingClasses = $classWithNamespace::$belongs_to;
                if(\is_array($belongingClasses)) {
                    foreach($belongingClasses as $belongClass) {
                        if(!isset($belongClass["class_name"]) || $belongClass["class_name"] == $classWithNamespace) continue;

                        if(!isset($this->_deletedClasses[$belongClass["class_name"]]) && class_exists($belongClass["class_name"])) {
                            $className = explode("\\", $belongClass["class_name"]);
                            $className = end($className);
                            $class = $this->addClass($className);
                            foreach($class["has_many"] as $index => $value) {
                                if($value["class_name"] == $classWithNamespace) {
                                    unset($this->_classes[$className]["has_many"][$index]);
                                }
                            }
                        }
                    }
                }
            }
            if(property_exists($classWithNamespace, "has_many")) {
                $hasManyClasses = $classWithNamespace::$has_many;
                if(\is_array($hasManyClasses)) {
                    foreach($hasManyClasses as $hasManyClass) {
                        if(!isset($hasManyClass["class_name"]) || $hasManyClass["class_name"] == $classWithNamespace) continue;
                        
                        if(!isset($this->_deletedClasses[$hasManyClass["class_name"]]) && class_exists($hasManyClass["class_name"])) {
                            $className = explode("\\", $hasManyClass["class_name"]);
                            $className = end($className);
                            $class = $this->addClass($className);
                            foreach($class["belongs_to"] as $index => $value) {
                                if($value["class_name"] == $classWithNamespace) {
                                    unset($this->_classes[$className]["belongs_to"][$index]);
                                }
                            }
                        }
                    }
                }
            }
            // you need to save the classes, who are already deleted, because php is caching the classes
            $this->_deletedClasses[$classWithNamespace] = $classWithNamespace;
            unlink($filename);
        }

        // fluent interface
        return $this;
    }

    /**
     * Method for adding a active record
     * 
     * @param string $class The name of the active record
     * 
     * @return array The array, which represents the class
     */
    protected function &addClass($class, $hasPosition = false) {
        $class = ucfirst(strtolower($class));
        $this->_allClasses[$class] = true;
        if(isset($this->_classes[$class])) {
            if($hasPosition) {
                $this->_classes[$class]["hasPosition"] = true;
            }
            return $this->_classes[$class];
        }
        
        $this->_classes[$class] = array(
            "table_name" => $this->_prefix.strtolower($class),
            "has_many" => array(),
            "belongs_to" => array(),
            "hasPosition" => $hasPosition
        );

        $classWithNamespace = $this->_modelNamespace.$class;
        if(class_exists($classWithNamespace)) {
            $this->_classes[$class]["table_name"] = $classWithNamespace::$table_name;
            $this->_classes[$class]["belongs_to"] = $classWithNamespace::$belongs_to;
            $this->_classes[$class]["has_many"] = $classWithNamespace::$has_many;
            $this->_classes[$class]["hasPosition"] = $classWithNamespace::$hasPosition;
        }
        
        return $this->_classes[$class];
    }
    
    /**
     * Method for sorting tables for creation 
     * 
     * @param array $tables      The tables to sort
     * @param array $foreignKeys The foreign keys
     */
    protected function sortTableQueue(array &$tables, array &$foreignKeys) {
        
        $sortedTables = array();
        $lastNumTables = -1;
        $deadlock = false;
        
        // are there tables to create?
        while(!empty($tables)) {
            $newSortedTables = array();
            foreach($tables as $table => $fields) {
                $toSorted = true;
                foreach($fields as $column => $info) {
                    if(is_string($info)) {
                        if($deadlock) {
                            $tableName = $info."_".$column;
                            if($tableName == $table || $column == "instance") {
                                continue;
                            }
                            // if there is a deadlock, remove the column and save
                            // the information about the foreign key
                            if(!isset($foreignKeys[$table])) {
                                $foreignKeys[$table] = array();
                            }
                            $foreignKeys[$table][$column] = $info;
                            unset($fields[$column]);
                            // add column
                            $fields[$column."_id"] = array("type" => "int", "isNull" => true);
                        } else {
                            // exists the foreign table and is the table not
                            // the own table (self-referential) -> do not create
                            // the table in this round
                            $tableName = $info."_".$column;
                            if(isset($tables[$tableName]) && $tableName != $table) {
                                $toSorted = false;
                            }
                        }
                    }
                }
                if($toSorted) {
                    $sortedTables[$table] = $fields;
                    $newSortedTables[] = $table;
                }
            }
            // delete the sorted tables out of the array
            foreach($newSortedTables as $sortedTable) {
                unset($tables[$sortedTable]);
            }
            if($deadlock) {
                break;
            }
            // check for deadlock
            $numTables = count($tables);
            if($numTables == $lastNumTables) {
                $deadlock = true;
            }
            $lastNumTables = $numTables;
        }
        $tables = $sortedTables;
    }
    
    /**
     * Method for creating tables 
     */
    protected function createTables() {
        $foreignKeys = array();
        $this->sortTableQueue($this->_tableCreateQueue, $foreignKeys);
        foreach($this->_tableCreateQueue as $table => $fields) {
            $this->createTable($table, $fields);
        }
        foreach($foreignKeys as $table => $columns) {
            $this->useTable($table);
            foreach($columns as $column => $module) {
                $this->useColumn($column."_id");
                $this->toForeignKey($module."_".$column);
            }
        }
        $this->_tableCreateQueue = array();
    }
    
    /**
     * Method for removing tables 
     */
    protected function removeTables() {
        while(!empty($this->_tableDeleteQueue)) {
            $deleted = array();
            foreach($this->_tableDeleteQueue as $table => $fields) {
                $this->removeTable($table);
                // if the error code is 00000, the execution of the sql was successful
                // and the table was deleted
                // if it's not 00000, try to delete this table in the next round
                if($this->errorCode() === "00000") {
                    $deleted[] = $table;
                }
            }
            foreach($deleted as $deletedTable) {
                unset($this->_tableDeleteQueue[$deletedTable]);
            }
        }
        $this->_tableDeleteQueue = array();
    }

    /**
     * Method for updating the active record classes
     * 
     * @return DBActiveRecordAdminister 
     */
    public function updateClasses() {
        if($this->uninstall) {
            // fluent interface
            return $this;
        }
        
        // create tables
        $this->createTables();
        
        // remove tables
        $this->removeTables();
        
        foreach($this->_allClasses as $classname => $value) {
            $classcontent = $this->_classes[$classname];
            $this->_classWriter->setClass($classname, null, "ActiveRecord");
            foreach($classcontent as $attributename => $attributecontent) {
                $this->_classWriter->addAttribute($attributename, "static", $attributecontent);
            }
            $this->_classWriter->save();
        }
        $this->_allClasses = array();

        // fluent interface
        return $this;
    }
    
    /**
     * Helper function for making a array unique
     * 
     * @param array &$array The array, which should be unique
     * 
     * @return null
     */
    private static function makeArrayUnique(array &$array) {
        array_walk($array, create_function('&$value,$key', '$value = json_encode($value);'));
        $array = array_unique($array);
        array_walk($array, create_function('&$value,$key', '$value = json_decode($value, true);'));
    }
    
    
    /**
     * Adds a data storage
     * (Implementation of interface DataManager)
     * 
     * @param string $name   The name of the data storage
     * @param array  $fields An array with info about the fields in the data storage
     * 
     * @return DataManager Fluent interface
     */
    public function createDataStorage($name, array $fields, array $options, $moduleName) {
        $newData = array();
        
        $defaultOptions = array(
            "revisions" => true,
            "users" => true
        );
        $options = array_merge($defaultOptions, $options);
        
        // revisions:
        // add a special instance self-referential column to the table
        // this column must come before the other columns,
        // because the class ActiveRecord makes a check, if this is set
        if($options["revisions"] === true) {
            $newData["revision"] = array("type" => "int");
            $newData["instance"] = $moduleName;
            $newData["deleted"] = array("type" => "boolean");
        }
        // permission system:
        // add user id
        if($options["users"] === true) {
            $newData["user"] = "permissions";
        }
        
        
        foreach($fields as $column => $info) {
            if(is_string($info)) {
                if(is_int($column)) {
                    $column = $info;
                    $info = $moduleName;
                }
            } elseif(is_null($info)) {
                $info = "pinion";
            }
            $newData[$column] = $info;
        }
        
        $this->_tableCreateQueue[$name] = $newData;
        
        // fluent interface
        return $this;
    }

    /**
     * Deletes a data storage
     * (Implementation of interface DataManager)
     * 
     * @param string $name The name of the data storage
     * 
     * @return DataManager Fluent interface
     */
    public function deleteDataStorage($name) {
        $this->_tableDeleteQueue[$name] = $name;
        
        // fluent interface
        return $this;
    }
    
    /**
     * Returns a data storage
     * (Implementation of interface DataManager)
     * 
     * @param string $name The name of the data storage
     * 
     * @return string The class of the data storage
     */
    public function getDataStorage($name) {
        $class = "\\pinion\\data\\models\\".ucfirst($name);
        if(!class_exists($class)) {
            throw new \Exception("Class '$class' does not exist!");
        }
        return $class;
    }
    
    /**
     * Adds data to a data storage
     * (Implementation of interface DataManager)
     * 
     * @param string $name The name of the data storage
     * @param array  $data An array with values of the new data
     * 
     * @return int The id of the added data
     */
    public function addData($name, array $data) {
        if(!empty($this->_tableCreateQueue)) {
            $this->updateClasses();
        }
        
        $class = $this->getDataStorage($name);
        return $class::create($data);
    }
    
    /**
     * Deletes data from a data storage
     * (Implementation of interface DataManager)
     * 
     * @param string $name The name of the data storage
     * @param int    $id   The identifier of the data
     * 
     * @return DataManager Fluent interface
     */
    public function deleteData($name, $id) {
        $this->getData($name, $id)->delete();
        
        return this;
    }
    
    /**
     * Updates data from a data storage
     * (Implementation of interface DataManager)
     * 
     * @param string $name    The name of the data storage
     * @param int    $id      The identifier of the data
     * @param array  $updates An array with values of the data
     * 
     * @return DataManager Fluent interface
     */
    public function updateData($name, $id, array $updates) {
        $data = $this->getData($name, $id);
        foreach($updates as $columnName => $columnValue) {
            $data->{$columnName} = $columnValue;
        }
        
        return this;
    }
    
    /**
     * Returns data from a data storage
     * (Implementation of interface DataManager)
     * 
     * @param string $name The name of the data storage
     * @param int    $id   The identifier of the data
     * 
     * @return mixed The data of the data storage with the given name and id
     */
    public function getData($name, $id) {
        // if there is a cached model, return the cached model
        if(!isset($this->_cachedData[$name])) {
            $this->_cachedData[$name] = array();
        }
        if(!isset($this->_cachedData[$name][$id])) {
            // create data
            $class = $this->getDataStorage($name);
            $this->_cachedData[$name][$id] = $class::find($id);
        }
            
        return $this->_cachedData[$name][$id];
    }
}
?>
