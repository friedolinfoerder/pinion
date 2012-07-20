<?php
/**
 * A logger, which logs the queries to the console.
 * It requires the class Console
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


class ConsoleLogger implements DBLogger {
    
    /**
     * Logs the sql to console
     * 
     * @param string $sql The sql of the query
     */
    public function log($sql) {
        \Console::log($sql);
    }
}

?>
