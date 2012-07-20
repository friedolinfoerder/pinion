<?php
/**
 * With the class Connector you can get connected with the homepage of
 * pinion-cms.org and so you can get information about modules, translations
 * and many more
 * 
 * PHP version 5.3
 * 
 * @category   access
 * @package    access
 * @subpackage access
 * @author     Friedolin Förder <friedolin.foerder@pinion-cms.org>
 * @license    MIT License
 * @link       http://www.pinion-cms.org
 */


namespace pinion\access;

use \pinion\files\DirectoryRearranger;


class Connector {
    
    /**
     * Checks for a connection with the pinion page
     * 
     * @return boolean True if connected, otherwise false
     */
    public static function isConnected() {
        $conn = fsockopen(substr(PINION_URL, 7), 80, $errno, $errstr, 5);
        
        if($conn) {
            fclose($conn);
            return true;
        } else {
            return false;
        }
    }
    
    /**
     * Provides information about a given resource
     * 
     * @param string $resource A resource like module or translation 
     * 
     * @return array An array with informations about the given resource 
     */
    public static function getInfo($resource) {
        if(!static::isConnected()) return array();
        
        $resource = strtolower($resource);
        $output = array();
        $json = file_get_contents(static::getJsonUrl()."/".$resource);
        return json_decode($json, true);
    }
    
    /**
     * Download and update/install a module
     * 
     * @param string $identifier
     * 
     * @return boolean True if the module was installed, otherwise false
     */
    public static function downloadModule($identifier) {
        $path = MODULES_PATH.$identifier;
        $update = "";
        if(file_exists($path)) {
            $update = "?current";
            if(file_exists($path."/info.ini")) {
                $ini = file_get_contents($path."/info.ini");
                $ini = parse_ini_string($ini, true);
                if(isset($ini["version"])) {
                    $update .= "=".urlencode($ini["version"]);
                }
            }
        }
        $content = @file_get_contents(static::getDownloadUrl()."/module/$identifier$update");
        if($content === false) {
            return false;
        }
        $tempPath = APPLICATION_PATH."files/temp";
        $tempName = $identifier.uniqid();
        $zipDir = "$tempPath/$tempName";
        $zipFile = "$zipDir.zip";
        file_put_contents($zipFile, $content);
        
        // unzip file
        DirectoryRearranger::unzip($zipFile);
        
        // enter maintenance mode
        Registry::enterMaintenanceMode();
        
        if(file_exists(MODULES_PATH.$identifier)) {
            // update module
            UpdateManager::updateModule($identifier, $zipDir."/".$identifier);
        } else {
            // install module
            DirectoryRearranger::copy($zipDir."/".$identifier, MODULES_PATH.$identifier);
        }
        
        // exit maintenance mode
        Registry::exitMaintenanceMode();
        
        // remove zip directory
        DirectoryRearranger::remove($zipDir);
        
        // remove zip file
        unlink($zipFile);
        
        return true;
    }
    
    /**
     * Gets information about a tranlations of a language
     * 
     * @param string $dir The acronym of the language
     * 
     * @return array An array with information about translations of a language
     */
    protected static function getTranslationsInfo($dir) {
        $json = file_get_contents(static::getJsonUrl()."/pinion/translations/$dir/info.json");
        $languageFiles = json_decode($json);
        $files = array();
        foreach($languageFiles as $languageFile) {
            $languageFileString = explode("_", $languageFile);
            array_shift($languageFileString);
            $languageFileString = join("_", $languageFileString);
            $languageFileString = explode(".", $languageFileString);
            array_pop($languageFileString);
            $languageFileString = join(".", $languageFileString);
            $files[] = array(
                "id" => $languageFile,
                "name" => $languageFileString
            );
        }
        return $files;
    }
    
    /**
     * Get the url for downloads
     * 
     * @return string The url for downloads 
     */
    protected static function getDownloadUrl() {
        return "http://download".substr(PINION_URL, 10);
    }
    
    /**
     * Get the url for json information
     * 
     * @return string The url for json information
     */
    protected static function getJsonUrl() {
        return "http://json".substr(PINION_URL, 10);
    }
    
    /**
     * Post information to a given page
     * 
     * @param string $url      The url of a page
     * @param array  $postdata The post data, which should be transfered
     */
    public static function post($url, array $postdata = array()) {
        $context = stream_context_create(array(
            "http" => array(
                "method" => "POST",
                "header" => "Accept-language: en\r\nContent-type: application/x-www-form-urlencoded\r\n",
                "content" => http_build_query($postdata)
            )
        ));
        file_get_contents($url, false, $context);
    } 
}

?>
