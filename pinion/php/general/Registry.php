<?php
/**
 * With the Registry class you can get and set all general settings
 * from everywhere in the application.
 * 
 * PHP version 5.3
 * 
 * @category   general
 * @package    general
 * @subpackage general
 * @author     Friedolin Förder <friedolin.foerder@pinion-cms.org>
 * @license    MIT License
 * @link       http://www.pinion-cms.org
 */


namespace pinion\general;

use \pinion\data\models\Pinion_setting;


class Registry {
    
    /**
     * An ActivRecord with settings
     * 
     * @var ActiveRecord $_settings 
     */
    protected static $_settings;
    
    /**
     * Flag for checking, if the head revision was incremented 
     * 
     * @var boolean $_hasHeadRevisionIncremented
     */
    protected static $_hasHeadRevisionIncremented = false;
    
    /**
     * Flag for checking, if new revisions are allowed
     * 
     * @var boolean $_newRevisionsAllowed
     */
    protected static $_newRevisionsAllowed = true;
    
    /**
     * An array with supported languages
     * 
     * @var array $_supportedLanguages
     */
    protected static $_supportedLanguages;
    
    /**
     * The current identity object
     * 
     * @var null|\stdClass $_identity 
     */
    protected static $_identity;

    /**
     * Get the settings
     * 
     * @return ActiveRecord An ActiveRecord with the settings 
     */
    public static function getSettings() {
        if(is_null(self::$_settings)) {
            self::$_settings = Pinion_setting::find("first");
        }
        return self::$_settings;
    }
    
    /**
     * Get the head revision by increment the revision number
     * 
     * @return int The head revision 
     */
    public static function getHeadRevision() {
        $settings = self::getSettings();
        if(!self::$_hasHeadRevisionIncremented) {
            if(self::newRevisionsAllowed()) {
                $settings->headrevision++;
                self::$_hasHeadRevisionIncremented = true;
                $settings->save();
            }
        }
        return $settings->headrevision;
    }
    
    /**
     * Get the current revision
     * 
     * @return int The current revision 
     */
    public static function getCurrentRevision() {
        $settings = self::getSettings();
        return $settings->headrevision;
    }
    
    /**
     * Checks if the page is in the maintenance mode
     * 
     * @return boolean True if the page is in maintenance mode, false otherwise 
     */
    public static function inMaintenanceMode() {
        $settings = self::getSettings();
        return $settings->maintenance;
    }
    
    /**
     * Enter the maintenance mode 
     */
    public static function enterMaintenanceMode() {
        $settings = self::getSettings();
        $settings->maintenance = true;
        $settings->save();
    }
    
    /**
     * Leave the maintenance mode 
     */
    public static function exitMaintenanceMode() {
        $settings = self::getSettings();
        $settings->maintenance = false;
        $settings->save();
    }
    
    /**
     * Get the language acronym of the page
     * 
     * @return string The language acronym 
     */
    public static function getLanguage() {
        $settings = self::getSettings();
        
        return $settings->language;
    }
    
    /**
     * Set the language acronym of the page
     * 
     * @param string $language The language acronym
     */
    public static function setLanguage($language) {
        $settings = self::getSettings();
        
        $settings->language = $language;
        $settings->save();
    }
    
    /**
     * Set the template of the page
     * 
     * @param string $name The name of the template
     */
    public static function setTemplate($name) {
        $settings = self::getSettings();
        
        $settings->template = $name;
        $settings->save();
    }
    
    /**
     * Get the template of the page
     * 
     * @return string The name of the template 
     */
    public static function getTemplate() {
        $settings = self::getSettings();
        
        return $settings->template;
    }
    
    /**
     * Get the timezone of the page
     *
     * @return string The timezone of the page 
     */
    public static function getTimezone() {
        $settings = self::getSettings();
        
        return $settings->timezone;
    }
    
    /**
     * Set the timezone of the page
     * 
     * @param string The timezone of the page 
     */
    public static function setTimezone($timezone) {
        $settings = self::getSettings();
        
        $settings->timezone = $timezone;
        $settings->save();
    }
    
    /**
     * Get the sitename of the page 
     * 
     * @return string The name of the page 
     */
    public static function getSiteName() {
        $settings = self::getSettings();
        
        return $settings->sitename;
    }
    
    /**
     * Set the sitename of the page
     * 
     * @param string $sitename The name of the page 
     */
    public static function setSiteName($sitename) {
        $settings = self::getSettings();
        
        $settings->sitename = $sitename;
        $settings->save();
    }
    
    /**
     * Get all supported languages
     * 
     * @return array An array with supported languages 
     */
    public static function getSupportedLanguages() {
        if(!self::$_supportedLanguages) {
            $settings = self::getSettings();
            self::$_supportedLanguages = array_merge(array($settings->language), array_keys(json_decode($settings->translations, true)));
        }
        return self::$_supportedLanguages;
    }
    
    /**
     * Get the date formats
     * 
     * @return array An array with date formats 
     */
    public static function getDateFormats() {
        $settings = self::getSettings();
        
        $dateformats = array();
        $dateformats[$settings->language] = json_decode($settings->dateformat, true);
        
        $translations = self::getTranslations();
        foreach($translations as $language => $df) {
            $dateformats[$language] = $df;
        }
        return $dateformats;
    }
    
    /**
     * Set the date formats
     * 
     * @param array $array An array with date formats
     */
    public static function setDateFormats(array $array) {
        $settings = self::getSettings();
        
        $translations = self::getTranslations();
        if(isset($array[$settings->language])) {
            $settings->dateformat = json_encode($array[$settings->language]);
            unset($array[$settings->language]);
        }
        foreach($array as $language => $dateformats) {
            $translations[$language] = $dateformats;
        }
        self::setTranslations($translations);
    }
    
    /**
     * Get all languages, which should be translated
     * 
     * @return array An array with all languages, which should be translated 
     */
    public static function getTranslations() {
        $settings = self::getSettings();
        return json_decode($settings->translations, true);
    }
    
    /**
     * Set the languages, which should be translated
     * 
     * @param array $array An array with all languages, which should be translated
     */
    public static function setTranslations(array $array) {
        $settings = self::getSettings();
        $settings->translations = json_encode($array);
        $settings->save();
    }
    
    /**
     * Stop the revisioning 
     */
    public static function stopRevisioning() {
        self::$_newRevisionsAllowed = false;
    }
    
    /**
     * Start the revisioning 
     */
    public static function startRevisioning() {
        self::$_newRevisionsAllowed = true;
    }
    
    /**
     * Checks if there are new revisions allowed
     * 
     * @return boolean True if new revisions allowed, false otherwise
     */
    public static function newRevisionsAllowed() {
        // only allow revisions when you logged in
        if(self::$_identity) {
            return self::$_newRevisionsAllowed;
        } else {
            return false;
        }
    }
    
    /**
     * Get the update interval, in which the user information should be requested
     * 
     * @return int The seconds between two ajax requests
     */
    public static function getUpdateInterval() {
        return 20;
    }
    
    /**
     * Get the id of the user, who is logged in
     * 
     * @return null|int Null if no user is logged in, otherwise the id of the user
     */
    public static function getUserId() {
        if(self::$_identity) {
            return self::$_identity->userid;
        }
        return null;
    }
    
    /**
     * Set the identity of the user
     * 
     * @param \stdClass $identity The identity of the user
     */
    public static function setIdentity($identity) {
        self::$_identity = $identity;
    }
}

?>
