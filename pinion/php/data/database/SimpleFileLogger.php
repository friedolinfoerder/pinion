<?php
/**
 * A logger, which logs the queries to an text file.
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

class SimpleFileLogger implements DBLogger {
    
    /**
     * The file extension for the file to write
     * 
     * @var string $_fileExtension
     */
    protected $_fileExtension = "txt";
    
    /**
     * The name of the file to write
     * 
     * @var string $_fileName 
     */
    protected $_fileName = "";
    
    /**
     * An array with logs
     * 
     * @var array $_logs 
     */
    protected $_logs = array();
    
    /**
     * The constructor of SimpleFileLogger 
     */
    public function __construct() {
        list($seconds) = explode(" ", microtime());
        list($seconds, $millis) = explode(".", $seconds);
        $startDate = date("Y-m-h H-i-s-").$millis;
        
        $this->_fileName = __DIR__."/logs/$startDate.$this->_fileExtension";
    }
    
    /**
     * Logs the sql
     * 
     * @param string $sql The sql of the query
     */
    public function log($sql) {
        if(isset($this->_logs[$sql])) {
            $this->_logs[$sql] = array();
        } 
        $this->_logs[] = array(
            "sql" => $sql,
            "microtime" => microtime(true)
        );
        
//        $text = $this->write($sql, $this->logs[$sql]);
//        file_put_contents($this->fileName, $text, FILE_APPEND);
    }
    
    /**
     * Writes a log
     * 
     * @param array $log Information about the log
     */
    protected function write($log) {
        return "{$log["sql"]}\n";
    }
    
    /**
     * Writes all of the file content
     * 
     * @return string The file content
     */
    protected function writeFileContent() {
        return null;
    }
    
    /**
     * The Destructor of SimpleFileLogger
     */
    public function __destruct() {
        if(empty($this->_logs)) return;
        
        $output = "";
        $fileContent = $this->writeFileContent();
        foreach($this->_logs as $log) {
            $output .= $this->write($log);
        }
        if($fileContent != null) {
            $output = sprintf($fileContent, $output);
        }
        
        
        file_put_contents($this->_fileName, $output, FILE_APPEND);
    }
}

?>
