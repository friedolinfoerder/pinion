<?php
/**
 * Class Module
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
/**
 * If you want create a module, you must extend this class (or the class
 * FrontendModule). It provides methods, which can be overwritten to create the
 * module functionality you want.
**/
namespace pinion\modules;

use \pinion\modules\ModuleManager;
use \pinion\data\DataManager;
use \pinion\events\EventDispatcher;
use \pinion\general\Response;
use \pinion\general\Request;
use \pinion\general\Session;
use \pinion\events\Event;
use \pinion\data\Data;
use \pinion\transfer\Framework;
use \pinion\data\models\Pinion_module;


abstract class Module extends EventDispatcher {
    
    /**
     * The ModuleManager
     * 
     * @var ModuleManager $moduleManager 
     */
    public static $moduleManager;
    
    /**
     * The DataManager
     * 
     * @var DataManager $dataManager 
     */
    public static $dataManager;
    
    /**
     * The permissions of the module
     * 
     * @var array $permissions
     */
    private $permissions;
    
    /**
     * The identity object
     * 
     * @var \stdClass $identity 
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
     * The replacements of the module
     * 
     * @var array $replacements
     */
    protected $replacements;
    
    /**
     * Informations about the module 
     * 
     * @var array $information
     */
    public $information;
    
    /**
     * The name of the module
     * 
     * @var string $name
     */
    public $name;
    
    /**
     * The template of the module
     * 
     * @var null|string $template
     */
    public $template = null;
    
    /**
     * The access to the data of the module
     * 
     * @var Data $data
     */
    public $data;
    
    /**
     * The access to the framework components for the backend
     * 
     * @var Framework $framework
     */
    public $framework;
    
    /**
     * The id of the currently rendered module 
     * 
     * @var int $context
     */
    protected $context;
    
    /**
     * A flag, which says if the module has run once
     * 
     * @var boolean $hasRun
     */
    protected $hasRun = false;
    
    /**
     * Set the context
     * 
     * @param int $id The id of the current module
     * 
     * @return \pinion\modules\Module Returns this
     */
    public function setContext($id) {
        $this->context = $id;
        $this->data->context = $id;
        // fluent interface
        return $this;
    }
    
    /**
     * Set the listener of module
     */
    public function addListener() {
        if($this->hasPermission("backend")) {
            $this->addEventListener("backend", "defineBackend");
        }
    }
    
    /**
     * Set the information about the module
     * 
     * @param array $info Information about the module
     * 
     * @return \pinion\modules\Module Returns this
     */
    public final function setInformation(array $info) {
        $this->name = $info["name"];
        $this->information = $info["info"];
        
        // fluent interface
        return $this;
    }
    
    /**
     * Set the DataManager 
     * 
     * @param DataManager $dataManager The DataManager
     * 
     * @return \pinion\modules\Module Returns this
     */
    public final function setDataManager(DataManager $dataManager) {
        self::$dataManager = $dataManager;
        
        $this->data = new Data($this->name, $dataManager);
        
        // fluent interface
        return $this;
    }
    
    /**
     * Get the name of the module
     * 
     * @return string The name of the module 
     */
    public final function getName() {
        return $this->name;
    }
    
    /**
     * Set the replacements for the module
     * 
     * @param array $replacements An array with replacements of the module
     * 
     * @return \pinion\modules\Module Returns this
     */
    public final function setReplacements(array $replacements) {
        $this->replacements = $replacements;
        
        // fluent interface
        return $this;
    }
    
    /**
     * Get the replacements of the module
     * 
     * @return array An array with replacements of the module 
     */
    public final function getReplacements() {
        return $this->replacements;
    }
    
    /**
     * Set the response object
     * 
     * @param Response $response The response object
     * 
     * @return \pinion\modules\Module Returns this
     */
    public final function setResponse(Response $response) {
        $this->response = $response;
        
        // fluent interface
        return $this;
    }
    
    /**
     * Set the request object
     * 
     * @param Request $request The request object
     * 
     * @return \pinion\modules\Module Returns this
     */
    public final function setRequest(Request $request) {
        $this->request = $request;
        
        // fluent interface
        return $this;
    }
    
    /**
     * Set the session object
     * 
     * @param Session $session The session object
     * 
     * @return \pinion\modules\Module Returns this
     */
    public final function setSession(Session $session) {
        $this->session = $session;
        
        // fluent interface
        return $this;
    }

    /**
     * The identity object
     * 
     * @param null|\stdClass $identity An identity object if logged in, false otherwise
     * 
     * @return \pinion\modules\Module Returns this
     */
    public final function setIdentity($identity) {
        $this->identity = $identity;
        $this->permissions = $this->_getModulePermissions($identity);
        
        // set backend, so you can define backend elements
        $this->framework = new Framework($this, $this->response, $this->session);
        
        // fluent interface
        return $this;
    }
    
    /**
     * Checks if the user has the permission for the given resource
     * 
     * @param string $name The name of the resource
     * 
     * @return boolean True if the user has the permission for the given resource, false otherwise
     */
    public final function hasPermission($name) {
        return isset($this->permissions[$name]);
    }

    /**
     * magic method: Return the name of the module
     * 
     * @return string The name of the module 
     */
    public final function __toString() {
        return $this->name;
    }
    
    /**
     * Get the permissions of the user for this module
     * 
     * @return array An array with resources 
     */
    public final function getPermissions() {
        return $this->permissions;
    }

    /**
     * Get the resources of the module
     * 
     * @return array The resources of the module 
     */
    public function getResources() {
        return array(
            "backend"
        );
    }
    
    /**
     * Revert the ActiveRecord
     * 
     * @param int $data     The ActiveRecord to revert
     * @param int $revision The number of the version
     */
    public function revert($data, $revision) {
        $data->revert($revision);
    }
    
    /**
     * Get the permissions of the user for this module
     * 
     * @param \stdClass $identity The identity object
     * 
     * @return array The permissions of the user for this module
     */
    protected function _getModulePermissions($identity) {

        $permissions = array();
        $permissionsModule = $this->module("permissions");
        
        if(!$identity) return $permissions;
        
        // user in group 'Administrators' has all permissions
        if($identity->rule == "Administrators" || !$permissionsModule) {
            $permissions = array_flip($this->getResources());
            return $permissions;
        }

        $ruleIds = $identity->ruleids;

        foreach($ruleIds as $ruleId) {
            $dbpermissions = $permissionsModule->data->all("resource", array("conditions" => array("rule_id = ? AND module = ?", $ruleId, $this->name)));
            if($dbpermissions !== null) {
                foreach($dbpermissions as $resource) {
                    if($resource->allow == true) {
                        $permissions[$resource->permission] = $resource->permission;
                    } elseif(isset($permissions[$resource->permission])) {
                        unset($permissions[$resource->permission]);
                    }
                }
            }
        }

        return $permissions;
    }
    
    /**
     * use this function for do things, you must do to install this module correctly
     * (e.g. create all neccessary tables)
     */
    public function install() {
        
    }
    
    /**
     * use this function for things, you must do at the uninstall of this module
     */
    public function uninstall() {
        
    }
    
    /**
     * use this function instead of the constructor to make things at the start
     * (e.g. start another module)
     */
    public function init() {
        
    }
    
    /**
     * Run the module
     * 
     * @return \pinion\modules\Module Returns this
     */
    public final function run() {
        if(!$this->hasRun) {
            $this->hasRun = true;
            $this->_run();
        }
        // fluent interface
        return $this;
    }
    
    /**
     * use this function to make specific things once 
     * (e.g. add js or css)
     */
    protected function _run() {
        
    }
    
    /**
     * Get a module
     * 
     * @param string The name of the module
     * 
     * @return Module The module with the given name 
     */
    public function module($name = null) {
        if($name == null) {
            $name = $this->name;
        }
        return self::$moduleManager->module($name);
    }
    
    /**
     * Define your backend with the variable backend
     */
    public function defineBackend() {
        
    }
    
    /**
     * Translate a word
     * 
     * @param string $word A string, which should be translated
     * 
     * @return string The translated string 
     */
    public function translate($word) {
        return call_user_func_array(array(self::$moduleManager, "translate"), func_get_args());
    }
    
    /**
     * Set a translation
     * 
     * @param string       $language The language acronym
     * @param array|string $source   An array with translations or the filename of the source
     * 
     * @return boolean|\modules\Translation The translation module or false, if the translation was not a success
     */
    protected function setTranslation($language, $source) {
        return self::$moduleManager->setTranslation($language, $source, $this);
    }
    
    /**
     * add menu items to the main menu of the cms
     * 
     * @example return array("upload")
     * @example return array("upload file" => "upload")
     * @example return array("files->upload")
     * @example return array("files->upload file" => "upload")
     * 
     * @return array An array with menuitems 
     */
    public function menu() {
        return array();
    }
}

?>
