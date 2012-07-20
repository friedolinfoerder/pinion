<?php
/**
 * A logger, which logs the queries to an html file.
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

class HtmlFileLogger extends SimpleFileLogger {
    
    /**
     * The file extension for the file to write
     * 
     * @var string $_fileExtension
     */
    protected $_fileExtension = "html";
    
    /**
     * Writes a log
     * 
     * @param array $log Information about the log
     */
    protected function write($log) {
        $color = "#000";
        if(preg_match("/^SHOW COLUMNS FROM/", $log["sql"]))
            $color = "#f00";
        if(preg_match("/^SELECT/", $log["sql"]))
            $color = "#0f0";
        
        return "<div style='color:$color'>{$log["sql"]}</div>\n";
    }
    
    /**
     * Writes all of the file content
     * 
     * @return string The file content
     */
    protected function writeFileContent() {
        return "<html>\n<head>\n<title>Log</title>\n</head>\n<body>\n%s</body>\n</html>";
    }
}

?>
