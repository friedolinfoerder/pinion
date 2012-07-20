<?php
/**
 * 
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

use \PDO;

class DBAdminister extends PDO {
    
    const PRIMARY_KEY = "primary";
    const UNIQUE_KEY = "unique";
    const FOREIGN_KEY = "foreign";
    const CASCADE = "CASCADE";

    /**
     * The type of the tables of the database
     * 
     * @var string $_dbType 
     */
    protected $_dbType = "InnoDB";
    
    /**
     * The current table
     * 
     * @var string $_currentTable 
     */
    protected $_currentTable;
    
    /**
     * The current column
     * 
     * @var string $_currentColumn 
     */
    protected $_currentColumn;
    
    /**
     * The defaults for creating tables 
     * 
     * @var array $_defaults 
     */
    protected $_defaults = array();
    
    /**
     * An array with information about the database
     * 
     * @var array $_database 
     */
    protected $_database;
    
    /**
     * Flag to determine if the queries should be executed
     * 
     * @var boolean $_noExecution 
     */
    protected $_noExecution = false;
    
    /**
     * The tables of the current database
     * 
     * @var array $_tables
     */
    protected $_tables;
    
    /**
     * The cached foreign keys
     * 
     * @var array $_savedForeignKeys 
     */
    protected $_savedForeignKeys = array();
    
    /**
     * The prefix for the database tables
     * 
     * @var string $_prefix 
     */
    protected $_prefix = "";

    /**
     * Constructor of DBAdminister
     * 
     * @param string $file The file path of the database config
     */
    public function __construct($file) {
        if (!$settings = @parse_ini_file($file, true)) throw new NoDatabaseInformationException('Unable to open ' . $file . '.');

        $this->_database = $settings['database'];
        $this->_prefix = $settings['database']['prefix'];

        $dns =  $settings['database']['driver']
                .':host=' . $settings['database']['host']
                .((!empty($settings['database']['port'])) ? (';port=' . $settings['database']['port']) : '')
                .';dbname=' . $settings['database']['name'];

        parent::__construct($dns, $settings['database']['username'], $settings['database']['password']);
        $this->query("SET NAMES 'utf8'");

        $this->_defaults = array(
            "type"          => "varchar",
            "varcharLength" => 500,
            "intLength"     => 11,
            "translatable"  => true
        );
    }
    
    /**
     * Returns the salt
     * 
     * @return string The salt used to encode the password
     */
    public function getSalt() {
        return $this->_database["salt"];
    }

    /**
     * Returns the name of the database
     * 
     * @return string The name of the database 
     */
    public function getDatabase() {
        return $this->_database['name'];
    }
    
    /**
     * Sets the current table to the given table
     * 
     * @param string $tableName The name of the current table
     * 
     * @return DBAdminister Fluent interface
     */
    public function useTable($tableName) {
        $this->_currentTable = $tableName;

        // fluent interface
        return $this;
    }

    /**
     * Sets the current column to the given column
     * 
     * @param string $columnName The name of the current column
     * 
     * @return DBAdminister Fluent interface
     */
    public function useColumn($columnName) {
        if($this->_currentTable == null) return false;

        $this->_currentColumn = $columnName;

        // fluent interface
        return $this;
    }

    /**
     * Method for creating a table
     * 
     * @param string $name    The name of the table to create
     * @param array  $columns The columns of the table
     * 
     * @return DBAdminister Fluent interface
     */
    public function createTable($name, array $columns=array()) {
        $name = strtolower($name);
        
        $columnsTemp = $columns;
        foreach($columnsTemp as $columnName => $columnInfo) {
            if(isset($columnInfo["type"]) && ($columnInfo["type"] === "varchar" || $columnInfo["type"] === "text") && (!isset($columnInfo["translatable"]) || $columnInfo["translatable"] !== false)) {
                $columns[$columnName]["isNull"] = true;
                $columns[$columnName."_translationid"] = array("type" => "int", "isNull" => true);
            }
        }
        
        // use this table
        $this->useTable($name);
        
        // add this table to the existing tables cache
        if(is_null($this->_tables)) {
            $this->getTables();
        }
        $this->_tables[$this->_prefix.$name] = array();
        foreach($columns as $columnName => $columnInfo) {
            $this->_tables[$this->_prefix.$name][$columnName] = $columnInfo;
        }
        
        $sql = "CREATE TABLE IF NOT EXISTS `{$this->_prefix}$name` (%s) ENGINE = $this->_dbType CHARACTER SET utf8 COLLATE utf8_unicode_ci;";
        $columnsSql = array("`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY");
        $indexSql = array();
        foreach($columns as $columnName => $columnInfo) {
            $this->_currentColumn = $columnName;
            // automatic foreign key detection
            if(is_string($columnInfo)) {
                if($columnName == "instance") {
                    $foreignTable = $this->_currentTable;
                } else {
                    $foreignTable = $columnInfo."_".$columnName;
                }
                $columnName = $columnName."_id";
                $columnInfo = array("type" => "int", "isNull" => true);
                
                $indexSql[] = "CONSTRAINT `{$columnName}in{$this->_prefix}$name` FOREIGN KEY (`$columnName`) REFERENCES `{$this->_prefix}$foreignTable` (`id`) ON DELETE CASCADE ON UPDATE CASCADE";
                
                $this->_currentColumn = $columnName;
                
                $this->_noExecution = true;
                $this->toForeignKey($foreignTable);
                $this->_noExecution = false;
            } else {
                $this->_tables[$this->_prefix.$name][$columnName] = $columnName;
            }
            
            $columnsSql[] = "`$columnName` ".$this->createColumnDefinition($columnInfo);
            
        }
        $columnsSql[] = "`updated` INT NOT NULL";
        $columnsSql[] = "`created` INT NOT NULL";
        
        $createSql = array_merge($columnsSql, $indexSql);
        $createSqlString = join(", ", $createSql);
        $sql = sprintf($sql, $createSqlString);
        
        // execute query
        $this->exec($sql);

        // fluent interface
        return $this;
    }

    /**
     * Method for adding a column to a table
     * 
     * @param string  $column         The name of the column
     * @param array   $columnInfo     An array with informations about the column
     * @param boolean $autoForeignKey Specify if the method should determine the foreign key automatically
     * 
     * @return DBAdminister Fluent interface
     */
    public function addColumn($column, array $columnInfo, $autoForeignKey=true) {
        if($this->_currentTable == null) return false;

        // use this column
        $this->useColumn($column);

        $sql = "ALTER TABLE `{$this->_prefix}{$this->_currentTable}` ADD `$column` ".$this->createColumnDefinition($columnInfo);

        // execute query
        $this->exec($sql);

        // automatic foreign key detection
        if($autoForeignKey && preg_match("/_id$/", $column)) {
            $this->toForeignKey();
        }

        // fluent interface
        return $this;
    }

    /**
     * Method for deleting a column of a table
     * 
     * @param string $column The name of the column to delete
     * 
     * @return DBAdminister Fluent interface
     */
    public function removeColumn($column) {
        if($this->_currentTable == null) return false;

        // remove foreign key
        $columnKey = $this->getKey($column);
        if($columnKey) {
            $this->removeForeignKey($column);
        }

        $sql = "ALTER TABLE `{$this->_prefix}{$this->_currentTable}` DROP `$column`";

        // execute query
        $this->exec($sql);

        // fluent interface
        return $this;
    }

    /**
     * Helper function for deleting an index of a table
     * 
     * @return DBAdminister Fluent interface
     */
    public function removeIndex() {
        if($this->_currentTable == null) return false;
        $this->removeKey("INDEX", func_get_args());

        // fluent interface
        return $this;
    }

    /**
     * Method for deleting a foreign key
     * 
     * @param string $column The name of the column
     * 
     * @return DBAdminister Fluent interface
     */
    public function removeForeignKey($column) {
        if($this->_currentTable == null) return false;

        $sql = "ALTER TABLE `{$this->_prefix}{$this->_currentTable}` DROP FOREIGN KEY `{$column}in{$this->_currentTable}`";

        // execute query
        $this->exec($sql);

        // fluent interface
        return $this;
    }

    /**
     * Helper function for deleting a specific key from a table
     * 
     * @param type  $type      The type of the key
     * @param array $arguments The colums, which are in the key
     * 
     * @return null
     */
    protected function removeKey($type, array $arguments) {
        $args = array();
        foreach($arguments as $arg) {
            $args[] = "`$arg`";
        }
        $columns = join(",", $args);
        
        $sql = "ALTER TABLE `{$this->_prefix}{$this->_currentTable}` DROP $type ($columns)";

        // execute query
        $this->exec($sql);
    }

    /**
     * Method for renaming a column
     * 
     * @param type $newName The old name of the column
     * @param type $oldName The new name of the column
     * 
     * @return DBAdminister Fluent interface
     */
    public function renameColumn($newName, $oldName) {
        if($this->_currentTable == null) return false;

        $cd = $this->getColumnDefinition($oldName);
        $columnInfo = array(
            "type" => $cd["Type"],
            "key" => $cd["Key"],
            "isNull" => $cd["Null"] == "NO" ? false : true,
        );
        $columnDefinition = $this->createColumnDefinition($columnInfo);

        $sql = "ALTER TABLE `{$this->_prefix}{$this->_currentTable}` CHANGE `$oldName` `$newName` $columnDefinition";

        // execute query
        $this->exec($sql);

        // use this column
        $this->useColumn($newName);

        // fluent interface
        return $this;
    }

    /**
     * Method for removing a table
     * 
     * @param string $tableName The name of the table
     * 
     * @return DBAdminister Fluent interface
     */
    public function removeTable($tableName) {
        $sql = "DROP TABLE `{$this->_prefix}$tableName`";
        
        if(!is_null($this->_tables) && isset($this->_tables[$this->_prefix.$tableName])) {
            unset($this->_tables[$this->_prefix.$tableName]);
        }

        // execute query
        $this->exec($sql);

        // fluent interface
        return $this;
    }

    /**
     * Method for emptying a table
     * 
     * @param string $table The name of the table
     * 
     * @return DBAdminister Fluent interface
     */
    public function emptyTable($table) {
        $sql = "TRUNCATE TABLE `{$this->_prefix}$table`";

        // execute query
        $this->exec($sql);

        // use this table
        $this->useTable($table);

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
     * @return DBAdminister 
     */
    public function toForeignKey($primaryTable=null, $foreignKey=null, $onDelete="CASCADE", $onUpdate="CASCADE") {
        if($this->_currentTable == null) return false;

        if($foreignKey == null) {
            $foreignKey = $this->_currentColumn;
        }

        if($primaryTable == null) {
            $otherTableName = explode("_", $foreignKey);
            $otherTableName = $otherTableName[0];
            $primaryTable = $otherTableName;
        }

        $this->createTableIfNotAvailable($primaryTable);
        
        $sql = "ALTER TABLE `{$this->_prefix}{$this->_currentTable}` ADD CONSTRAINT `{$foreignKey}in{$this->_prefix}{$this->_currentTable}` FOREIGN KEY (`$foreignKey`) REFERENCES `{$this->_prefix}$primaryTable` (`id`) ON DELETE $onDelete ON UPDATE $onUpdate;";

        // execute query
        $this->exec($sql);

        // use this column
        $this->useColumn($foreignKey);

        // fluent interface
        return $this;
    }
    
    /**
     * Helper function to create a table, and don't change the current context
     * 
     * @param type $name The name of the table to create
     */
    private function createTableIfNotAvailable($name) {
        if(!$this->tableExist($name)) {
            $currentTableUse = $this->_currentTable;
            $this->createTable($name);
            $this->useTable($currentTableUse);
        }
    }

    /**
     * Method for adding an unique key
     * 
     * @param string $column,... unlimited OPTIONAL number of columns, which are in the unique key
     * 
     * @return DBAdminister Fluent interface
     */
    public function addUniqueKey() {
        if($this->_currentTable == null) return false;
        $this->addKey("UNIQUE", func_get_args());

        // fluent interface
        return $this;
    }

    /**
     * Method for adding an index
     * 
     * @param string $column,... unlimited OPTIONAL number of columns, which are in the unique key
     * 
     * @return DBAdminister Fluent interface
     */
    public function addIndex() {
        if($this->_currentTable == null) return false;
        $this->addKey("INDEX", func_get_args());

        // fluent interface
        return $this;
    }

    /**
     * Helper method for adding any key to the a table
     * 
     * @param string $type      The type of the key
     * @param array  $arguments The columns, which are in the key
     * 
     * @return null
     */
    protected function addKey($type, array $arguments) {
        $args = array();

        if(empty($arguments)) {
            $arguments[] = $this->_currentColumn;
        }

        foreach($arguments as $arg) {
            $args[] = "`$arg`";
        }
        $columns = join(",", $args);

        $sql = "ALTER TABLE `{$this->_prefix}{$this->_currentTable}` ADD $type ($columns)";

        // execute query
        $this->exec($sql);
    }

    /**
     * The method returns a column definition
     * 
     * @param string $column The name of the column
     * 
     * @return string The column definition of the column
     */
    protected function getColumnDefinition($column) {
        $creationSql = $this->query("SHOW COLUMNS FROM `{$this->_prefix}{$this->_currentTable}`")->fetchAll(PDO::FETCH_ASSOC);
        foreach($creationSql as $cs) {
            if($cs["Field"] == $column) {
                return $cs;
            }
        }
    }

    /**
     * The method returns the key for a given column name 
     * 
     * @param string $column The name of the column
     * 
     * @return string|boolean The key name if the column has a key, false otherwise
     */
    protected function getKey($column) {
        $columnDef = $this->getColumnDefinition($column);
        $key = $columnDef["Key"];
        if(empty($key)) {
            return false;
        } else {
            return $key;
        }
    }

    /**
     * The method creates a column definition
     * 
     * @param array $columnInfo An array with informations about the column
     * 
     * @return string A valid column definition, which can be used in table definition 
     */
    protected function createColumnDefinition(array $columnInfo) {
        $output = "";
        $isNull = (isset($columnInfo["isNull"]) && $columnInfo["isNull"] == true) ? "NULL" : "NOT NULL";
        $type = $columnInfo["type"] ? strtoupper($columnInfo["type"]) : strtoupper($this->_defaults["type"]);
        $length = "";
        if(! preg_match("/\\(.*\\)/u", $type) && $type != "BOOLEAN" && $type != "TEXT") {
            if(isset($columnInfo["length"])) {
                $length = $columnInfo["length"];
            } elseif($type == "INT") {
                $length = $this->_defaults["intLength"];
            } elseif($type == "VARCHAR") {
                $length = $this->_defaults["varcharLength"];
            }
            $length = "(".$length.")";
        }
        if($type == "TEXT") {
            $length = "";
        }
        
        $key = "";
        if(isset($columnInfo["key"])) {
            switch ($columnInfo["key"]) {
                case (self::PRIMARY_KEY):
                    $key = "PRIMARY KEY";
                    break;
                case (self::UNIQUE_KEY):
                    $key = "UNIQUE KEY";
                    break;
            }
        }

        $output = "$type$length $isNull $key";

        return $output;
    }

    /**
     * The method returns all names of tables in the current database
     * 
     * @return array An array with all tables in the current database 
     */
    public function getTables() {
        // lazy function, only make an sql query if this query was not made before
        if($this->_tables == null) {
            $sql = "SHOW TABLES";
            $result = $this->query($sql)->fetchAll(PDO::FETCH_ASSOC);
            $this->_tables = array();
            foreach($result as $row) {
                foreach($row as $tablename) {
                    $this->_tables[$tablename] = null;
                }
            }
        }
        return $this->_tables;
    }

    /**
     * Checks if a table exists in the current database
     * 
     * @param string $table The name of the table
     * 
     * @return boolean True if the given table exists, false otherwise 
     */
    protected function tableExist($table) {
        $table = strtolower($table);
        $tables = $this->getTables();
        return isset($tables[$this->_prefix.$table]);
    }

    /**
     * Returns the columns of a table
     * 
     * @param string $table The name of the table 
     * 
     * @return array The array with the names of the column 
     */
    protected function getColumns($table) {
        $tables = $this->getTables();
        if(!$this->tableExist($table)) {
            return false;
        }
        // lazy function, only make an sql query if this query was not made before
        if($this->_tables[$this->_prefix.$table] == null) {
            $sql = "SHOW COLUMNS FROM `{$this->_prefix}$table`";
            $result = $this->query($sql)->fetchAll(PDO::FETCH_ASSOC);
            $this->_tables[$this->_prefix.$table] = array();
            foreach($result as $row) {
                $this->_tables[$this->_prefix.$table][$row["Field"]] = $row["Field"];
            }
        }
        return $this->_tables[$this->_prefix.$table];
    }

    /**
     * Checks if a column exists in a table
     * 
     * @param string $table  The name of the table
     * @param string $column The name of the column
     * 
     * @return boolean True if the column exists, false otherwise 
     */
    protected function columnExist($table, $column) {
        if(!$this->tableExist($table)) {
            return false;
        }
        if(isset($this->_tables[$this->_prefix.$table][$column])) {
            return true;
        }
        $columns = $this->getColumns($table);
        return isset($columns[$column]);
    }

    /**
     * Create the basis for a many to many relationship
     * 
     * @param string $firstColumn  The column of the first table
     * @param string $secondColumn The column of the second table
     * 
     * @return DBAdminister 
     */
    public function manyToMany($firstColumn, $secondColumn) {
        $this
            ->addColumn($firstColumn, array("type" => "int"))
            ->addColumn($secondColumn, array("type" => "int"));

        // fluent interface
        return $this;
    }

    /**
     * Method for creating a one to many relationship
     * 
     * @param string $column  The name of the column
     * @param array  $options optional options for the columns
     * 
     * @return DBAdminister Fluent interface
     */
    public function oneToMany($column, array $options = array()) {
        $createOptions = \array_merge(array("type" => "int", "isNull" => true), $options);
        $this->addColumn($column, $createOptions);

        // fluent interface
        return $this;
    }

    /**
     * Method for creating a self referential relationship in the current table
     * 
     * @param array $options optional options for the columns
     * 
     * @return DBAdminister Fluent interface
     */
    public function selfReferential(array $options = array()) {
        $createOptions = \array_merge(array("type" => "int", "isNull" => true), $options);
        $this->addColumn($this->_currentTable."_id", $createOptions, false);
        $this->toForeignKey($this->_currentTable);

        // fluent interface
        return $this;
    }
    
    /**
     *
     * @param string $statement The sql statement
     * 
     * @return int|boolean Number of effected rows or false if the execution is not allowed
     */
    public function exec($statement) {
        if($this->_noExecution) {
            return false;
        }
        
        $result = parent::exec($statement);
        
        return $result;
    }
}
?>
