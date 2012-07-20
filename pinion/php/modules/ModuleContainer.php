<?php
/**
 * Every ModuleContainer is related to a module. The ModuleContainer determinates,
 * if this module is usable and can be started. If this is the case, it will be
 * create a instance of the module.
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

use \pinion\data\database\DBActiveRecordAdminister;
use \pinion\data\DataManager;
use \pinion\general\Request;
use \pinion\general\Response;
use \pinion\general\Session;
use \pinion\files\DirectoryRearranger;


class ModuleContainer {
    
    const DISABLED      = 0;
    const ENABLED       = 1;
    const USABLE        = 2;
    
    /**
     * The DataManager
     * 
     * @var DataManager $dataManager
     */
    public static $dataManager;
    
    /**
     * The container, which are checking at the moment
     * 
     * @var type $_currentCheckingContainer
     */
    protected static $_currentCheckingContainer = array();
    
    /**
     * The status of the container
     * 
     * @var int $status
     */
    public $status;

    /**
     * An instance of the ArModuleStatusChecker
     * 
     * @var ArModuleStatusChecker $statusChecker 
     */
    protected $statusChecker;
    
    /**
     * flag, if a module was already checked
     * 
     * @var boolean $checked
     */
    public $checked = false;

    /**
     * The module instance
     * 
     * @var Module $module 
     */
    public $module;
    
    /**
     * An array of other ModuleContainers represents the dependencies of the module
     * 
     * @var array $dependencies
     */
    protected $dependencies = array();
    
    /**
     * An array of other ModuleContainers represents the requirements of the module
     * 
     * @var array $requiredFrom
     */
    protected $requiredFrom = array();
    
    /**
     * A other ModuleContainer, which represents the replacement of the module
     * 
     * @var ModuleContainer $replacement 
     */
    public $replacement;
    
    /**
     * The name of the module
     * 
     * @var string $name 
     */
    protected $name;
    
    /**
     * The title of the module 
     * 
     * @var string $title
     */
    protected $title;
    
    
    /**
     * The category of the module
     * 
     * @var string $category
     */
    protected $category;
    
    /**
     * True if core, false otherwise
     * 
     * @var boolean $core
     */
    protected $core;
    
    /**
     * The version of the module
     * 
     * @var string $version
     */
    protected $version;


    /**
     * An array of other ModuleContainers represents the references to other modules
     * 
     * @var ModuleContainer $backReferences
     */
    public $backReferences = array();
    
    /**
     * The response object
     * 
     * @var Response $response 
     */
    protected $response;
    
    /**
     * The constructor of ModuleContainer
     * 
     * @param array                 $info          An array with information about the module
     * @param ArModuleStatusChecker $statusChecker An instance of the ArModuleStatusChecker
     * @param Response              $response      The response object
     */
    public function __construct(array $info, ArModuleStatusChecker $statusChecker, Response $response) {
        $this->name = $info["name"];
        $this->title = $info["title"];
        $this->category = $info["category"];
        $this->core = $info["core"];
        $this->version = $info["version"];
        
        $this->response = $response;
        
        $this->statusChecker = $statusChecker;
        $this->status = $statusChecker->isEnabled($this->name) ? self::ENABLED : self::DISABLED;
    }
    
    /**
     * Get the name of the module
     * 
     * @return string The name of the module
     */
    public function getName() {
        return $this->name;
    }
    
    /**
     * Get the title of the module
     * 
     * @return string The title of the module
     */
    public function getTitle() {
        return $this->title;
    }
    
    /**
     * Add a dependency to the dependencies of the module
     * 
     * @param ModuleContainer|null $dependency A ModuleContainer, which represents the dependency
     * 
     * @return ModuleContainer Fluent interface returns this
     */
    public function addDependency($dependency) {
        
        if($dependency != null) {
            $this->dependencies[$dependency->getName()] = $dependency;
            $dependency->requiredFrom[] = $this;
        } else {
            $this->dependencies[] = null;
        }
        
        // fluent interface
        return $this;
    }
    
    /**
     * Checks if the ModuleContainer has the given dependency
     * 
     * @param ModuleContainer $dependency The dependency
     * 
     * @return boolean true, if this has the given dependency, false otherwise 
     */
    public function hasDependency(ModuleContainer $moduleContainer) {
        return isset($this->dependencies[$moduleContainer->getName()]);
    }
    
    /**
     * Checks if a module has a back reference
     * 
     * @param ModuleContainer $backReference The reference to another ModuleContainer
     * 
     * @return boolean true, if this has the given backReference, false otherwise 
     */
    public function hasBackReference(ModuleContainer $moduleContainer) {
        return isset($this->backReferences[$moduleContainer->getName()]);
    }
    
    /**
     * This is the main function for checking the dependencies of a module.
     * This function determinates, if a module is usable.
     *
     * @return boolean true, if this is usable, false otherwise 
     */
    public function check() {
        // start the checking, so every other container knows, that this
        // container is checking now
        $this->startChecking();
        
        if($this->status == self::DISABLED) {
            $this->setUnusable();
            
            $this->stopChecking();
            return false;
        } elseif($this->checked) {
            
            $this->stopChecking();
            return ($this->status == self::USABLE);
        }
        
        $this->checked = true;
        
        $backReferenceIsSet = false;
        
        foreach($this->dependencies as $dependency) {
            if($dependency == null) {
                // if the dependency does not exists, the module is unusable
                $this->setUnusable();
                
                $this->stopChecking();
                return false;
            } elseif($dependency->isChecking()) {
                // if the dependency is currently checking, this particular dependency is circular
                // and so it is temporarly usable
                // because it is only a temporarly state, you must reset the checked state
                $this->checked = false;
                // continue with the next dependency
                continue;
            } elseif($dependency->hasDependency($this)) {
                // only add backReference, when the dependency doesn't have
                // this backReference
                if(!$dependency->hasBackReference($this)) {
                    // now add the backReference to the this moduleContainer
                    $this->addBackReference($dependency);
                    // call this function recursively
                    if(! $dependency->check()) {
                        $this->setUnusable();
                        
                        $this->stopChecking();
                        return false;
                    }
                } else {
                    // if there is a backReference, avoid setting this
                    // moduleContainer directly to usable
                    $backReferenceIsSet = true;
                }
            // call this function recursively
            } elseif(! $dependency->check()) {
                $this->setUnusable();
                
                $this->stopChecking();
                return false;
            }
        }
        
        // iterate over all backReferences and make all backReferences usable,
        // because this moduleContainer is also usable
        foreach($this->backReferences as $backReference) {
            $backReference->setUsable();
        }
        // if there is no backReference set, the status can set directly to usable
        if(!$backReferenceIsSet) {
            $this->setUsable();
        }
        $this->stopChecking();
        return true;
    }
    
    /**
     * Set a module usable
     */
    public function setUsable() {
        if(!$this->statusChecker->isUsable($this->name)) {
            $this->statusChecker->setUsable($this->name, true);
            
            $title = $this->statusChecker->getTitle($this->name);
            $moduleManager = ModuleManager::getInstance();
            $this->response->addMessage($this->name, $moduleManager->translate(false, "The module %s is now <b>usable</b>.", "<b>".$moduleManager->translate($title)."</b>"));
        }
        $this->status = self::USABLE;
    }
    
    /**
     * Set a module unusable
     */
    public function setUnusable() {
        if($this->statusChecker->isUsable($this->name)) {
            $this->statusChecker->setUsable($this->name, false);

            $title = $this->statusChecker->getTitle($this->name);
            $moduleManager = ModuleManager::getInstance();
            $this->response->addMessage($this->name, $moduleManager->translate(false, "The module %s has <b>stopped working</b>.", "<b>".$moduleManager->translate($title)."</b>"));
        }
    }
    
    /**
     * Start checking if a module is uable
     */
    public function startChecking() {
        self::$_currentCheckingContainer[$this->name] = true;
    }
    
    /**
     * Stop checking if a module is usable 
     */
    public function stopChecking() {
        unset(self::$_currentCheckingContainer[$this->name]);
    }
    
    /**
     * Checks if a ModuleContainer is checking at the moment
     * 
     * @return boolean True if the ModuleContainer is checking at the moment, false otherwise 
     */
    public function isChecking() {
        return isset(self::$_currentCheckingContainer[$this->name]);
    }
    
    /**
     * Add a back reference to the ModuleContainer
     * 
     * @param ModuleContainer $moduleContainer A other ModuleContainer
     * 
     * @return ModuleContainer Returns this
     */
    public function addBackReference(ModuleContainer $moduleContainer) {
        $this->backReferences[$moduleContainer->getName()] = $moduleContainer;
        
        // fluent interface
        return $this;
    }
    
    /**
     * Get the module if usable
     * 
     * @param ModuleManager            $moduleManager The ModuleManager
     * @param DBActiveRecordAdminister $db            The database administer object
     * @param null|\stdClass           $identity      The identity object
     * @param Request                  $request       The request object
     * @param Session                  $session       The session object
     * @param array                    $replacements  The replacements of the ModuleContainer
     * 
     * @return null|Module If the module is usable, it returns the module, null otherwise 
     */
    public function getModule(ModuleManager $moduleManager, DBActiveRecordAdminister $db, $identity, Request $request, Session $session, $replacements = array()) {
        if(!$this->statusChecker->isUsable($this->name))
            return null;
        
        // if there is an replacement, call the getModule-function of the
        // replacement
        // if there is no replacement, search for a replacement with the
        // statusChecker and set the replacement (lazy)
        if($this->replacement != null) {
            // recursive call
            $replacements[] = $this->name;
            return $this->replacement->getModule($moduleManager, $db, $identity, $request, $replacements);
        } elseif($this->statusChecker->hasReplacement($this->name)) {
            $this->replacement = $moduleManager->getModuleContainer($this->statusChecker->getReplacement($this->name));
            // recursive call
            $replacements[] = $this->name;
            return $this->replacement->getModule($moduleManager, $db, $identity, $request, $replacements);
        }
        
        if($this->module != null)
            return $this->module;
        
        $class = $this->getClassName();
        $this->module = new $class();
        $this->module
            ->setInformation(array(
                "name" => $this->name,
                "info" => array(
                    "title" => $this->title,
                    "category" => $this->category,
                    "core" => $this->core,
                    "version" => $this->version
                )
            ))
            ->setDataManager(self::$dataManager)
            ->setReplacements($replacements)
            ->setResponse($this->response)
            ->setRequest($request)
            ->setSession($session);
        
        // start all dependencies
        foreach($this->dependencies as $dependency) {
            // only start a dependency, if it wasn't started before
            // A module was not started before, when the module attribute of the
            // moduleContainer is null
            if($dependency->module == null) {
                $dependency->getModule($moduleManager, $db, $identity, $request, $session);
            }
        }
        
        
        if(!$this->statusChecker->isInstalled($this->name)) {
            DirectoryRearranger::create(APPLICATION_PATH."files/modules/".$this->name);
            self::$dataManager->updateClasses();
            $this->module->install();
            // file_put_contents(APPLICATION_PATH."modules.txt", $this->module->name, FILE_APPEND);
            self::$dataManager->updateClasses();
            $this->statusChecker->setInstalled($this->name);
        }
        
        $this->module
            ->setIdentity($identity)
            ->init();
        $this->module->addListener();
        
        return $this->module;
    }
    
    /**
     * magic method: Get the state of the module
     * 
     * @return string A information about the module and the state 
     */
    public function __toString() {
        if($this->status == self::DISABLED)
            $status = "disabled";
        elseif($this->status == self::ENABLED)
            $status = "enabled";
        elseif($this->status == self::USABLE)
            $status = "usable";
        return "ModuleContainer: $this->name, Status: $status";
    }
    
    /**
     * Uninstall the module
     * 
     * @param array $toUninstall The array with modules to uninstall
     * 
     * @return boolean Return true, if the module was uninstalled, false otherwise
     */
    public function uninstallModule(array &$toUninstall) {
        if(!$this->statusChecker->isInstalled($this->name)) return true;
        if($this->status != self::DISABLED) return false;
        
        $toUninstall[$this->name] = $this->name;
        
        $class = $this->getClassName();
        $module = new $class();
        $module
            ->setInformation(array(
                "name" => $this->name,
                "info" => array(
                    "title" => $this->title,
                    "category" => $this->category,
                    "core" => $this->core,
                    "version" => $this->version
                )
            ))
            ->setDataManager(self::$dataManager)
            ->uninstall();
        
        $tables = self::$dataManager->getTables();
        foreach($tables as $name => $columns) {
            if(substr($name, 0, strlen($this->name)) === $this->name) {
                self::$dataManager->deleteDataStorage($name);
            }
        }
        self::$dataManager->updateClasses();
        
        // trigger event uninstall
        ModuleManager::getInstance()->dispatchEvent("uninstall", array(
            "module" => $module->name
        ));
        
        $this->statusChecker->setInstalled($this->name, false);
        
        $contents = ModuleManager::getInstance()->module("page")->data->find_all_by_module("content", $this->name);
        foreach($contents as $content) {
            $content->delete(true);
        }
        
        DirectoryRearranger::remove(APPLICATION_PATH."files/modules/".$this->name);
        
        return true;
    }
    
    /**
     * Get the class name and the namespace of the module
     * 
     * @return string The name of the module class with full namespace
     */
    protected function getClassName() {
        // get class name with namespace
        return "\\modules\\$this->name\\".ucfirst($this->name);
    }
}

?>
