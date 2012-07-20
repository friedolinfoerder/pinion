<?php
/**
 * Module Modulesinfo
 * 
 * PHP version 5.3
 * 
 * @author  Friedolin Förder <friedolin.foerder@pinion-cms.org>
 * @license MIT License
 * @link    http://www.pinion-cms.org/module/modulesinfo
 */

namespace modules\modulesinfo;

use \pinion\modules\Module;
use \pinion\modules\ModuleManager;
use \pinion\events\Event;
use \pinion\files\DirectoryRearranger;
use \pinion\access\Connector;

class Modulesinfo extends Module {
    
    protected $_reload = false;
    
    public function install() {
        
        $this->module("translation")->setBackendTranslation("de", array(
            "module-managing" => "Modulverwaltung"
        ));
    }
    
    public function init() {
        
    }
    
    public function getResources() {
        return array_merge(parent::getResources(), array(
            "install module",
            "uninstall module",
            "enable module",
            "disable module",
            // TODO "update module"
        ));
    }
    
    public function addListener() {
        parent::addListener();
        
        if($this->hasPermission("enable module")) $this->addEventListener("enableModule");
        if($this->hasPermission("disable module")) $this->addEventListener("disableModule");
        if($this->hasPermission("install module")) $this->addEventListener("installModule");
        if($this->hasPermission("uninstall module")) $this->addEventListener("uninstallModule");
        
        $this->addEventListener("refresh");
        
        if($this->identity) {
            $this->response->addEventListener("flushInfos", "checkForReload", $this);
        }
    }
    
    public function installModule(Event $event) {
        $identifier = $event->getInfo("module identifier");
        
        // download file
        $ok = Connector::downloadModule($identifier);
        
        if($ok) {
            $this->response->addSuccess($this, $this->translate("The module %s has been installed", "<b>$identifier</b>"));
        } else {
            $this->response->addError($this, $this->translate("The module %s was not installed", "<b>$identifier</b>"));
        }
        
        
        // reload modules
        $this->_reload = true;
    }
    
    public function uninstallModule(Event $event) {
        // start all modules (so you can hear to listener)
        self::$moduleManager->startModules();
        
        $modules = $event->getInfo("modules");
        foreach($modules as $module) {
            self::$moduleManager->uninstall($module);
            $this->response->addSuccess($this, $this->translate("The module %s has been uninstalled", "<b>$module</b>"));
        }
        $this->_reload = true;
    }
    
    public function checkForReload() {
        if($this->_reload) {
            self::$moduleManager->reloadModules();
            $this->response->setInfo("restart", true);
        }
    }
    
    public function enableModule(Event $event) {
        self::$moduleManager->enableModules($event->getInfo("modules"));
        $this->_reload = true;
    }
    
    public function disableModule(Event $event) {
        self::$moduleManager->disableModules($event->getInfo("modules"));
        $this->_reload = true;
    }
    
    public function defineBackend() {
        
        // reload modules
        self::$moduleManager->reloadModules();
        
        // get info about all modules on the server
        $info = Connector::getInfo("module");
        
        // get all modules
        $modules = \pinion\data\models\Pinion_module::all();
        
        // save the module manager in a variable, so it can be used in closures
        $moduleManager = self::$moduleManager;
        
        $data = $this->data->getAttributes($modules, array(
            "*",
            "dependencies" => function($value) use($moduleManager) {
                $deps = json_decode($value);
                $dependencies = array();
                foreach($deps as $dependency) {
                    $moduleExists = $moduleManager->moduleExists($dependency);
                    if($moduleExists) {
                        $moduleIsUsable = $moduleManager->moduleIsUsable($dependency);
                        $moduleIsEnabled = $moduleManager->moduleIsEnabled($dependency);
                    }
                    $dependencies[$dependency] = array(
                        "exist" => $moduleExists,
                        "usable" => $moduleIsUsable,
                        "enabled" => $moduleIsEnabled
                    );
                }
                return $dependencies;
            },
            "icon" => function($ar) {
                return ModuleManager::getIcon($ar->name);
            },
            "update" => function($ar) use($info) {
                $module = $ar->name;
                if(isset($info[$module]) && isset($ar->version)) {
                    if(isset($info[$module]["hasUpdates"][$ar->version])) {
                        return $info[$module]["hasUpdates"][$ar->version];
                    }
                } else {
                    return null;
                }
            }
        ));
        
        // create module list with data
        $this->framework
            ->startGroup("LazyMainTab", array("title" => "Modules"))
                ->list("Finder", array(
                    "data" => $data,
                    "renderer" => "ModuleRenderer",
                    "scrollable" => false,
                    "selectable" => false
                ));
        
        $this->framework
            ->startGroup("LazyMainTab", array("title" => "Install"))
                ->input("Textbox", array(
                    "label" => "module identifier",
                    "events" => array(
                        "event" => "installModule"
                    )
                ));
    }
    
    public function menu() {
        return array(
            "module-managing" => "Modules",
            "install module" => "Install"
        );
    }
    
}
?>
