<?php
/**
 * This class checks the status of the modules.
 * You can get information about all modules, such as if an module
 * exists or if the module is enabled and installed.
 * 
 * PHP version 5.3
 * 
 * @category   modules
 * @package    modules
 * @subpackage modules
 * @author     Friedolin Förder <friedolin.foerder@pinion-cms.org>
 * @license    MIT License
 * @link       http://www.pinion-cms.org
 */


namespace pinion\modules;

use \pinion\data\models\Pinion_module;


class ArModuleStatusChecker {

    /**
     * The name of the ActiveRecord
     * 
     * @var string $arname 
     */
    protected $arname;
    
    /**
     * The available modules
     * 
     * @var array $modules 
     */
    protected $modules = array();
    
    /**
     * Get information about a module
     * 
     * @param string $modulename The name of the module
     * 
     * @return null|Pinion_module null if the module doesn't exist, otherwise the module 
     */
    protected function get($modulename) {
        $modulename = strtolower($modulename);
        
        if($this->modules == null) {
            $this->getModulesFromDB();
        }
        
        return isset($this->modules[$modulename]) ? $this->modules[$modulename] : null;
    }
    
    /**
     * Get all modules with the help of the database 
     */
    protected function getModulesFromDB() {
        
        $modules = Pinion_module::all();

        foreach($modules as $module) {
            $this->modules[$module->name] = $module;
        }
        
    }
    
    /**
     * Add a module to the existing modules
     * 
     * @param string        $modulename The name of the module
     * @param Pinion_module $module     An ActiveRecord of the module
     */
    protected function add($modulename, $module) {
        
        if($this->modules == null) {
            $this->getModulesFromDB();
        }
        // add to the array modules
        $this->modules[$modulename] = $module;
    }
    
    /**
     * Checks if the module is available
     * 
     * @param string $name The name of the module
     * 
     * @return boolean True if the module is available, false otherwise 
     */
    public function isAvailable($name) {
        return ($this->get($name) != null);
    }
    
    /**
     * Checks if the module is enabled
     * 
     * @param string $name The name of the module
     * 
     * @return boolean True if the module is enabled, false otherwise
     */
    public function isEnabled($name) {
        return $this->get($name)->enabled;
    }
    
    /**
     * Create or update a module in the database
     * 
     * @param string       $name         The name of the module
     * @param null|string  $title        The title of the module
     * @param null|string  $description  The description of the module
     * @param null|boolean $core         True if the module is an core module, false otherwise
     * @param array        $dependencies An array with dependencies
     * @param string       $category     The category of the module
     * @param string       $version      The version of the module
     * @param string       $author       The author of the module
     */
    public function createOrUpdate($name, $title, $description, $core, $dependencies, $category, $version, $author) {
        $name = strtolower($name);
        
        if($this->isAvailable($name)) {
            $module = $this->get($name);
        } else {
            $module = new Pinion_module();
            $module->name = $name;
            $module->usable = false;
            // standard => disabled
            $module->enabled = false;
            $module->installed = false;
        }
        $module->title = $title;
        $module->description = $description;
        $module->core = $core;
        $module->replacement = null;
        $module->dependencies = json_encode($dependencies);
        $module->category = $category;
        $module->version = $version;
        $module->author = $author;
        
        $module->save();
        
        // add to the array modules
        $this->add($name, $module);
    }
    
    /**
     * Enable or disable a module
     * 
     * @param string  $module  The name of the module
     * @param boolean $enabled True to enabled the module, false to disable it
     */
    public function setEnabled($module, $enabled = true) {
        $ar = $this->get($module);
        $ar->enabled = $enabled;
        $ar->save();
    }

    /**
     * Synchronize the database with the directory structure
     * 
     * @param array $modules The available module directories
     */
    public function synchronize(array $modules) {

        $thisModules = $this->modules;
        foreach($thisModules as $modulename => $module) {
            if(! $modules[$modulename]) {
                $module->delete();
                unset($this->modules[$modulename]);
            }
        }
    }

    /**
     * Checks if a module is usable
     * 
     * @param string $name The name of the module
     * 
     * @return boolean True if the module is usable, false otherwise 
     */
    public function isUsable($name) {
        return $this->get($name)->usable;
    }

    /**
     * Make an module usable or unusable
     * 
     * @param string  $name   The name of the module
     * @param boolean $usable True to make the module usable, false to make it unusable
     */
    public function setUsable($name, $usable = true) {

        $module = $this->get($name);
        $module->usable = $usable;
        $module->save();
    }
    
    /**
     * Checks if an module is installed
     * 
     * @param string $name The name of the module
     * 
     * @return boolean True if the module is installed, false otherwise 
     */
    public function isInstalled($name) {
        return $this->get($name)->installed;
    }
    
    /**
     * Set a module to installed or to uninstalled
     * 
     * @param string  $name      The name of the module
     * @param boolean $installed True to set the module to installed, false to set it to uninstalled
     */
    public function setInstalled($name, $installed = true) {
        $module = $this->get($name);
        $module->installed = $installed;
        $module->save();
    }
    
    /**
     * Get the title of the module
     * 
     * @param string $name The name of the module
     * 
     * @return string The title of the module 
     */
    public function getTitle($module) {
        return $this->get($module)->title;
    }
    
    /**
     * Get the description of the module
     * 
     * @param string $name The name of the module
     * 
     * @return string The description of the module 
     */
    public function getDescription($module) {
        return $this->get($module)->description;
    }

    /**
     * Get all modules
     * 
     * @return array An array with all modules 
     */
    public function getModules() {
        if($this->modules == null) {
            $this->getModulesFromDB();
        }

        return $this->modules;
    }

    /**
     * Get the replacement of a module
     * 
     * @param string $name The name of the module
     * 
     * @return string The replacement of the module 
     */
    public function getReplacement($module) {

        return $this->get($module)->replacement;
    }

    /**
     * Checks if a module has a replacement
     * 
     * @param string $name The name of the module
     * 
     * @return boolean True if the module has a replacement, false otherwise 
     */
    public function hasReplacement($module) {

        return $this->get($module)->replacement ? true : false;
    }

    /**
     * Set the replacement of a module
     * 
     * @param string      $name        The name of the module
     * @param null|string $replacement The name of replacement, null to delete the replacement
     */
    public function setReplacement($module, $replacement = null) {

        $ar = $this->get($module);
        $ar->replacement = $replacement;
        $ar->save();
    }
}
?>
