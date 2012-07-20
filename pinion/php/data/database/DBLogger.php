<?php
/**
 * A simple logger interface.
 * So you can implement this interface and write your own logger.
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

interface DBLogger {
    
    /**
     * Logs the sql
     * 
     * @param string $sql The sql of the query
     */
    public function log($sql);
}

?>
