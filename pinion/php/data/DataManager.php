<?php
/**
 * This interface is obsolete. It only exists for backwards compatibility 
 * reasons.
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


interface DataManager {
    
    /**
     * Adds a data storage
     * 
     * @param string $name   The name of the data storage
     * @param array  $fields An array with info about the fields in the data storage
     * 
     * @return DataManager Fluent interface
     */
    public function createDataStorage($name, array $fields, array $options, $moduleName);
    
    /**
     * Deletes a data storage
     * 
     * @param string $name The name of the data storage
     * 
     * @return DataManager Fluent interface
     */
    public function deleteDataStorage($name);
    
    /**
     * Returns a data storage
     * 
     * @param string $name The name of the data storage
     * 
     * @return string The class of the data storage
     */
    public function getDataStorage($name);
    
    /**
     * Adds data to a data storage
     * 
     * @param string $name The name of the data storage
     * @param array  $data An array with values of the new data
     * 
     * @return int The id of the added data
     */
    public function addData($name, array $data);
    
    /**
     * Updates data from a data storage
     * 
     * @param string $name    The name of the data storage
     * @param int    $id      The identifier of the data
     * @param array  $updates An array with values of the data
     * 
     * @return DataManager Fluent interface
     */
    public function updateData($name, $id, array $updates);
    
    /**
     * Returns data from a data storage
     * 
     * @param string $name The name of the data storage
     * @param int    $id   The identifier of the data
     * 
     * @return mixed The data of the data storage with the given name and id
     */
    public function getData($name, $id);
}

?>
