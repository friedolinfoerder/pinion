<?php
/**
 * The ModuleManager administer all modules.
 * If you want to use a module, you can get this module with the ModuleManager.
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
use \pinion\general\Response;
use \pinion\general\Request;
use \pinion\general\Session;
use \pinion\modules\ArModuleStatusChecker;
use \pinion\events\EventDispatcher;

class ModuleManager extends EventDispatcher {
    
    /**
     * An instance of the DBActiveRecordAdminister
     * 
     * @var DBActiveRecordAdminister $db
     */
    protected $db;
    
    /**
     * The path to the directory of the modules
     * 
     * @var string $modDir
     */
    protected $modDir;
    
    /**
     * An array with ModuleContainer
     * 
     * @var array $moduleContainer
     */
    protected $moduleContainer = array();
    
    /**
     * An instance of an ArModuleStatusChecker
     * 
     * @var ArModuleStatusChecker $statusChecker 
     */
    protected $statusChecker;
    
    /**
     * An array of modules, which should be uinstalled
     * 
     * @var array $modulesToUninstall
     */
    protected $modulesToUninstall = array();
    
    /**
     * An array of modules, which should be installed
     * 
     * @var array $modulesToInstall
     */
    protected $modulesToInstall = array();
    
    /**
     * An array with usable modules
     * 
     * @var array $usableModules 
     */
    protected $usableModules = array();
    
    /**
     * The identity of the user
     * 
     * @var null|\stdClass $identity 
     */
    protected $identity;
    
    /**
     * The response object
     * 
     * @var Response $response 
     */
    protected $response;
    
    /**
     * The request object
     * 
     * @var Request $request 
     */
    protected $request;
    
    /**
     * The session object
     * 
     * @var Session $session 
     */
    protected $session;
    
    /**
     * True if the modules were already started, false otherwise
     * 
     * @var boolean $started
     */
    protected $started = false;
    
    /**
     * Singleton: An instance of this
     * 
     * @var ModuleManager $_instance
     */
    protected static $_instance;
    
    /**
     * Singleton: Get this instance
     * 
     * @return ModuleManager This instance 
     */
    public static function getInstance() {
        return self::$_instance;
    }

    /**
     * The constructor of ModuleManager
     * 
     * @param string                   $pathToModDir  The string to the modules directory
     * @param DBActiveRecordAdminister $administer    An instance of the DBActiveRecordAdminister
     * @param ArModuleStatusChecker    $statusChecker An instance of the ArModuleStatusChecker
     * @param Response                 $response      The response object
     * @param Request                  $request       The request object
     * @param Session                  $session       The session object
     */
    public function __construct($pathToModDir, DBActiveRecordAdminister $administer, ArModuleStatusChecker $statusChecker, Response $response, Request $request, Session $session) {
        $this->modDir = $pathToModDir;
        $this->db = $administer;
        $this->statusChecker = $statusChecker;
        $this->response = $response;
        $this->request = $request;
        $this->session = $session;
        
        self::$_instance = $this;
        Module::$moduleManager = $this;
        ModuleContainer::$dataManager = $administer;
    }

    /**
     * Set the identity
     * 
     * @param null|\stdClass $identity The identity of the user
     * 
     * @return \pinion\modules\ModuleManager Returns this
     */
    public function setIdentity($identity) {
        $this->identity = $identity;
        
        // fluent interface
        return $this;
    }

    /**
     * Singleton: Do not clone the object
     */
    public function __clone() {
        // overwriting of this magic method, because you should not have two
        // ModuleManager
    }

    /**
     * Load all modules
     * 
     * @return \pinion\modules\ModuleManager Returns this
     */
    public function loadModules() {
        $this->moduleContainer = array();
        
        // only there is a session or a post parameter 'refresh'
        // all directories will be scaned and the dependencies will be refreshed
        if($this->session->hasParameter("refresh")) {
            $this->session->unsetParameter("refresh");
            $this->_loadWithDirectories();
        } elseif($this->identity && $this->request->hasRequestParameter("refresh")) {
            $this->_loadWithDirectories();
        } else {
            // if there is no 'refresh' parameter, load the modules with the
            // the information in the database (this is a lot faster)
            $this->_loadWithSavedInformations();
        }
        
        // fluent interface
        return $this;
    }
    
    /**
     * Load all modules with the information in the database 
     */
    protected function _loadWithSavedInformations() {
        $dependencies = array();
        
        // create module container
        foreach($this->statusChecker->getModules() as $module) {
            $this->moduleContainer[$module->name] = new ModuleContainer(array(
                "name" => $module->name,
                "title" => $module->title ?: $module->name,
                "category" => $module->category,
                "core" => (boolean)$module->core,
                "version" => $module->version
            ), $this->statusChecker, $this->response);
            
            if($module->replacement != null) {
                $replacements[$module->replacement] = $module->name;
            }
            
            // dependencies
            $dependencies[$module->name] = json_decode($module->dependencies);
        }
        
        // set replacements
        foreach($this->statusChecker->getModules() as $module) {
            if($module->replacement != null) {
                $this->moduleContainer[$module->name]->replacement = $this->moduleContainer[$module->replacement];
            }
        }
        
        // set the dependencies
        $this->_setDependencies($dependencies);
    }
    
    /**
     * Reload the resources of the administrators 
     */
    public function reloadAdministrationResources() {
        $resources = array();
        foreach($this->usableModules as $moduleName => $usableModule) {
            $resources[$moduleName] = array_flip($usableModule->getResources());
        }
        $this->identity->permissions = $resources;
    }
    
    /**
     * Reload the modules 
     */
    public function reloadModules() {
        $this->_loadWithDirectories();
    }
    
    /**
     * Load all modules with the directory structure and the ini files
     */
    protected function _loadWithDirectories() {
        $dependencies = array();
        $replacements = array();
        
        $modDir = new \DirectoryIterator($this->modDir);
        foreach($modDir as $file) {
            
            if(!$file->isDot() && $file->isDir()) {
                if(file_exists($file->getPathname().DIRECTORY_SEPARATOR.ucfirst($file->getFilename()).".php")) {
                    $class = $file->getPathname().DIRECTORY_SEPARATOR.$file->getFilename();
                    $inifile = $file->getPathname().DIRECTORY_SEPARATOR."info.ini";

                    $info = array();
                    if(file_exists($inifile)) {
                        $info = parse_ini_file($inifile, true);
                    }

                    $modulename = strtolower($file->getFilename());
                    
                    $dependenciesOfModule = array();
                    if(isset($info["dependencies"])) {
                        $dependenciesOfModule = str_replace(" ", "", $info["dependencies"]); // remove spaces
                        $dependenciesOfModule = strtolower($dependenciesOfModule); // make lowercase
                        $dependenciesOfModule = explode(",", $dependenciesOfModule); // convert to array
                        $dependencies[$modulename] = $dependenciesOfModule;
                    }
                    
                    $title = (isset($info["title"])) ? $info["title"] : $modulename;
                    
                    $description = (isset($info["description"])) ? $info["description"] : null;
                    
                    $core = (isset($info["core"]) && $info["core"] == true);
                    
                    $category = (isset($info["category"])) ? $info["category"] : "no category";
                    
                    $version = (isset($info["version"])) ? $info["version"] : "";
                    
                    $author = (isset($info["author"])) ? $info["author"] : "";
                    

                    if(isset($info["replace"])) {
                        $replacementFor = $info["replace"];
                        // only add a replacement, if there is no other replacement yet
                        if(!isset($replacements[$replacementFor])) {
                            $replacements[$replacementFor] = $modulename;
                        } else {
                            $this->response->addWarning($replacementFor, "The module '$replacementFor' is already replaced by the module '{$replacements[$replacementFor]}'."); // TODO translate
                        }
                        
                    }
                    
                    $this->statusChecker->createOrUpdate(
                        $modulename,
                        $title,
                        $description,
                        $core,
                        $dependenciesOfModule,
                        $category,
                        $version,
                        $author
                    );
                    
                    $this->moduleContainer[$modulename] = new ModuleContainer(array(
                        "name" => $modulename,
                        "title" => $title ?: $modulename,
                        "category" => $category,
                        "core" => (boolean)$core,
                        "version" => $version
                    ), $this->statusChecker, $this->response);
                }
            }
        }
        
        // refresh the replacements
        $this->_setReplacements($replacements);
        
        // set the dependencies
        $this->_setDependencies($dependencies);
        
        // check, if the modules are usable and can be started
        foreach($this->moduleContainer as $moduleContainer) {
            $moduleContainer->check();
        }
        
        // delete the old modules out of the database table
        $this->statusChecker->synchronize($this->moduleContainer);
        
        // start modules
        $this->startModules();
        
        // set the resources of the rule Administrators
        if($this->identity && $this->identity->rule == "Administrators") {
            $usableModules = array();
            foreach($this->moduleContainer as $modulename => $module) {
                $module = $this->module($modulename);
                if($module) {
                    $this->usableModules[$modulename] = $module;
                }
            }
            $this->reloadAdministrationResources();
        }
    }
    
    /**
     * Set the replacements
     * 
     * @param array $replacements An array with replacements
     */
    protected function _setReplacements(array $replacements) {
        foreach($replacements as $moduleContainerToReplace => $replacement) {
            if(isset($this->moduleContainer[$moduleContainerToReplace])) {
                // replacement = NULL
                $this->statusChecker->setReplacement($moduleContainerToReplace, $replacement);
                $this->moduleContainer[$moduleContainerToReplace]->replacement = $this->moduleContainer[$replacement];
            }
        }
    }
    
    /**
     * Set the dependencies
     * 
     * @param array $modulesWithDependencies An array with dependencies
     */
    protected function _setDependencies(array $modulesWithDependencies) {
        foreach($modulesWithDependencies as $moduleWithDependency => $dependencies) {
            $moduleContainer = array();
            foreach($dependencies as $dependency) {
                $dependency = isset($this->moduleContainer[$dependency]) ? $this->moduleContainer[$dependency] : null;
                $this->moduleContainer[$moduleWithDependency]->addDependency($dependency);
            }
        }
    }

    /**
     * Get a module
     * 
     * @param string $moduleName The name of the module
     * 
     * @return null|Module If the module is usable, it returns the module, null otherwise 
     */
    private function getModuleInstance($moduleName) {
        $moduleName = strtolower($moduleName);
        if(isset($this->moduleContainer[$moduleName])) {
            return $this->moduleContainer[$moduleName]->getModule($this, $this->db, $this->identity, $this->request, $this->session);
        } else {
            return null;
        }
    }
    
    /**
     * Get one or all ModuleContainer
     * 
     * @param null|string $name null to get all ModuleContainer, a name of a module to get one ModuleContainer
     * 
     * @return array|ModuleContainer A array with ModuleContainer or one ModuleContainer
     */
    public function getModuleContainer($name = null) {
        if($name != null) {
            $name = strtolower($name);
            return isset($this->moduleContainer[$name]) ? $this->moduleContainer[$name] : null;
        } else {
            return $this->moduleContainer;
        }
    }
    
    /**
     * Get all module names
     * 
     * @return array An array with all module names 
     */
    public function getModuleNames() {
        $array = array();
        foreach($this->moduleContainer as $modulename => $module) {
            $array[$modulename] = $modulename;
        }
        return $array;
    }
    
    /**
     * Get all usable modules
     * 
     * @return array An array with ModuleContainer  
     */
    public function getUsableModules() {
        return $this->startModules()->usableModules;
    }
    
    /**
     * Get all usable modules, which are instances of FrontendModule
     * 
     * @return array An array with ModuleContainer
     */
    public function getUsableFrontendModules() {
        $usableModules = $this->startModules()->usableModules;
        $usableFrontendModules = array();
        foreach($usableModules as $name => $usableModule) {
            if($usableModule instanceof FrontendModule) {
                $usableFrontendModules[$name] = $usableModule;
            }
        }
        return $usableFrontendModules;
    }

    /**
     * Start all usable modules
     * 
     * @return \pinion\modules\ModuleManager Returns this
     */
    public function startModules() {
        if($this->started) {
            return $this;
        }
        $this->usableModules = array();
        foreach($this->moduleContainer as $modulename => $module) {
            $module = $this->module($modulename);
            if($module) {
                $this->usableModules[$modulename] = $module;
                if($this->identity) {
                    $this->response
                        ->addCss(null, "backend:$modulename")
                        ->addJs(null, "backend:$modulename");
                }
            }
        }
        $this->started = true;
        // fluent interface
        return $this;
    }
    
    /**
     * Start one module
     * 
     * @param string $moduleName The name of the module
     * 
     * @return \pinion\modules\ModuleManager Returns this
     */
    public function start($moduleName) {
        $this->getModuleInstance($moduleName);
        
        // fluent interface
        return $this;
    }

    /**
     * Checks if an module exists
     * 
     * @param string $moduleName The name of the module
     * 
     * @return boolean True if the module exists, false otherwise
     */
    public function moduleExists($moduleName) {
        if($this->statusChecker->isAvailable($moduleName))
            return true;
        return false;
    }

    /**
     * Checks if an module is enabled
     * 
     * @param string $moduleName The name of the module 
     * 
     * @return boolean True if the module is enabled, false otherwise
     */
    public function moduleIsEnabled($moduleName) {
        if(! $this->moduleExists($moduleName))
            return false;
        return $this->statusChecker->isEnabled($moduleName);
    }

    /**
     * Checks if an module is usable
     * 
     * @param string $moduleName The name of the module
     * 
     * @return boolean True if the module is usable, false otherwise
     */
    public function moduleIsUsable($moduleName) {
        if(! $this->moduleExists($moduleName))
            return false;
        return $this->statusChecker->isUsable($moduleName);
    }

    /**
     * magic method: Get a module 
     * 
     * @param string $name The name of the module
     * 
     * @return null|Module The module if it exists, null otherwise 
     */
    public function  __get($name) {
        return $this->getModuleInstance($name);
    }
    
    /**
     * Get a module
     * 
     * @param string $name The name of the module
     * 
     * @return null|Module The module if it exists, null otherwise 
     */
    public function module($name) {
        return $this->getModuleInstance($name);
    }

    /**
     * Enable a module
     * 
     * @param string $modulename The name of the module
     */
    protected function enableModule($modulename) {
        if($this->moduleIsEnabled($modulename)) return;
        
        $this->statusChecker->setEnabled($modulename);
        
        $this->response->addSuccess($modulename, $this->translate("The module %s was enabled", "<b>".$this->getTitle($modulename)."</b>"));
    }

    /**
     * Enable many modules
     * 
     * @param array $modulenames An array with module names
     */
    public function enableModules(array $modulenames) {
        foreach($modulenames as $modulename) {
            $this->enableModule($modulename, true);
        }
    }

    /**
     * Disable a module
     * 
     * @param string $modulename The name of the module
     */
    protected function disableModule($modulename) {
        if(! $this->moduleIsEnabled($modulename)) return;

        $this->statusChecker->setEnabled($modulename, false);
        
        $this->response->addSuccess($modulename, $this->translate("The module %s was disabled", "<b>".$this->getTitle($modulename)."</b>"));
    }

    /**
     * Disable many modules
     * 
     * @param array $modulenames An array with module names
     */
    public function disableModules(array $modulenames) {
        foreach($modulenames as $modulename) {
            $this->disableModule($modulename, true);
        }
    }
    
    /**
     * Uninstall a module
     * 
     * @param string $moduleName The name of the module
     */
    public function uninstall($moduleName) {
        $name = strtolower($moduleName);
        
        $uninstalled = array();
        $this->moduleContainer[$name]->uninstallModule($uninstalled);
    }
    
    /**
     * Get the icon url of a module
     * 
     * @param string $module The name of the module
     * 
     * @return string The url of the icon 
     */
    public static function getIcon($module) {
        $module = strtolower($module);
        if(file_exists(MODULES_PATH.$module."/icon.png")) {
            return MODULES_URL."/".$module."/icon.png";
        } else {
            return SITE_URL."pinion/assets/images/icons/defaultModuleIcon.png";
        }
    }
    
    /**
     * Get the title of a module
     * 
     * @param string $modulename The name of a module
     * 
     * @return string The title of a module
     */
    public function getTitle($modulename) {
        return $this->translate($this->statusChecker->getTitle($modulename));
    }
    
    /**
     * Translate a string
     * 
     * @param string $word A string to translate
     * 
     * @return string The translated string 
     */
    public function translate(/* $translateAll = false,*/$word/*, $word ... */) {
        $translation = $this->module("translation");
        if($translation) {
            if(func_num_args() > 1) {
                $arguments = func_get_args();
                $translateAll = false;
                if(is_bool($arguments[0])) {
                    $translateAll = array_shift($arguments);
                }
                if($translateAll) {
                    foreach($arguments as &$argument) {
                        $argument = $translation->translateBackend($argument);
                    }
                } else {
                    $arguments[0] = $translation->translateBackend($arguments[0]);
                }
                return call_user_func_array("sprintf", $arguments);
            } else {
                return $translation->translateBackend($word);
            }
        }
        return $word;
    }
    
    /**
     * Set a translation
     * 
     * @param string       $language The language acronym
     * @param array|string $source   An array with translations or the filename of the source
     * @param null|Module  $module   A module instance or null
     * 
     * @return boolean|\modules\Translation The translation module or false, if the translation was not a success
     */
    public function setTranslation($language, $source, $module = null) {
        $translation = $this->module("translation");
        if(!$translation) return false;
        
        if(is_array($source)) {
            return $translation->setBackendTranslation($language, $source);
        } elseif(is_string($source) && $module != null) {
            return $translation->setBackendTranslationFile($language, MODULES_PATH.$module."/".$source);
        } else {
            return false;
        }
    }
}
?>