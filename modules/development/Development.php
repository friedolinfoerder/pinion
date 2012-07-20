<?php
/**
 * Module Development
 * 
 * PHP version 5.3
 * 
 * @author  Friedolin Förder <friedolin.foerder@pinion-cms.org>
 * @license MIT License
 * @link    http://www.pinion-cms.org/module/development
 */

namespace modules\development;


use \pinion\modules\Module;
use \pinion\events\Event;

/**
 * Description of Development
 * 
 * @category   data
 * @package    pinion
 * @subpackage database
 * @author     Friedolin Förder <friedolinfoerder@pinion-cms.de>
 * @license    license placeholder
 * @link       http://www.pinion-cms.de
 */
class Development extends Module {
    
    public function getResources() {
        
        return array_merge(parent::getResources(), array(
            "add module"
        ));
    }
    
    
    public function addListener() {
        parent::addListener();
        
        if($this->identity) {
            if($this->hasPermission("add module")) $this->addEventListener("addModule");
        }
    }
    
    
    public function addModule(Event $event) {
        if($event->hasInfo("name")) {
            $namespace = strtolower($event->getInfo("name"));
            $classname = ucfirst($namespace);
        } else {
            return $this->response->addWarning($this, $this->translate(true, "no %s given", "name"));
        }
        
        if($event->hasInfo("title")) {
            $title = $event->getInfo("title");
        } else {
            return $this->response->addWarning($this, $this->translate(true, "no %s given", "title"));
        }
        
        if($event->hasInfo("category")) {
            $category = $event->getInfo("category");
        } else {
            return $this->response->addWarning($this, $this->translate(true, "no %s given", "category"));
        }
        
        if($event->hasInfo("dependencies")) {
            $dependencies = $event->getInfo("dependencies");
        } else {
            $dependencies = "";
        }
        
        if($event->hasInfo("version")) {
            $version = $event->getInfo("version");
        } else {
            return $this->response->addWarning($this, $this->translate(true, "no %s given", "version"));
        }
        
        $type = $event->hasInfo("type") ? "FrontendModule" : "Module";
        
        if($event->hasInfo("author")) {
            $author = $event->getInfo("author");
        } else {
            return $this->response->addWarning($this, $this->translate(true, "no %s given", "author"));
        }
        
        if($event->hasInfo("resources")) {
            $resources = $event->getInfo("resources");
            $resources = explode(",", $resources);
            foreach($resources as $index => $resource) {
                $resources[$index] = trim($resource);
            }
        } else {
            $resources = null;
        }
        
        $code_init = $event->getInfo("code_init");
        $code_install = $event->getInfo("code_install");
        $code_uninstall = $event->getInfo("code_uninstall");
        
        if($event->hasInfo("events")) {
            $events = $event->getInfo("events");
            
            foreach($events as $index => $event) {
                if(isset($event["only if logged in"])) {
                    if(!isset($events["all"])) {
                        $events["all"] = array();
                    }
                    $events["all"][] = $event;
                } else {
                    if(!isset($events["loggedIn"])) {
                        $events["loggedIn"] = array();
                    }
                    $events["loggedIn"][] = $event;
                }
            }
        } else {
            $resources = null;
        }
        
        $path = MODULES_PATH.$namespace;
        if(!file_exists($path)) {
            // $namespace directory
            mkdir($path);
            
            // $classname.php file
            ob_start();
            include_once MODULES_PATH.$this->name."/blueprint/module.php";
            $data = ob_get_clean();
            file_put_contents($path."/".$classname.".php", $data);
            
            // info.ini file
            ob_start();
            include_once MODULES_PATH.$this->name."/blueprint/info.php";
            $data = ob_get_clean();
            file_put_contents($path."/info.ini", $data);
            
            // backend directory
            mkdir($path."/backend");
            mkdir($path."/backend/js");
            mkdir($path."/backend/css");
            
            
            if($type == "FrontendModule") {
                // templates directory
                mkdir($path."/templates");
                mkdir($path."/templates/js");
                mkdir($path."/templates/css");
                
                // _main.php file
                ob_start();
                include_once MODULES_PATH.$this->name."/blueprint/templates/_main.php";
                $data = ob_get_clean();
                file_put_contents($path."/templates/_main.php", $data);
                
                // Module.js file
                ob_start();
                include_once MODULES_PATH.$this->name."/blueprint/backend/js/Module.php";
                $data = ob_get_clean();
                file_put_contents($path."/backend/js/$classname.js", $data);
                
                // Preview.js file
                ob_start();
                include_once MODULES_PATH.$this->name."/blueprint/backend/js/Preview.php";
                $data = ob_get_clean();
                file_put_contents($path."/backend/js/Preview.js", $data);
                
                // Preview.css file
                ob_start();
                include_once MODULES_PATH.$this->name."/blueprint/backend/css/preview.php";
                $data = ob_get_clean();
                file_put_contents($path."/backend/css/preview.css", $data);
            }
            
        } else {
            $this->response->addError($this, $this->translate("There is already a module %s", "<b>$namespace</b>"));
        }
    }
    
    
    public function defineBackend() {
        
        $addModuleEvents = array(
            "event" => "addModule"
        );
        
        if($this->hasPermission("add module")) {
            $this->framework
                ->startGroup("LazyMainTab", array("title" => "New module", "groupEvents" => true))
                    ->startGroup("TitledSection", array("title" => "General information", "groupEvents" => true))
                        ->input("Textbox", array(
                            "label" => "name",
                            "help"  => "the name of the module<br />this is also the identifier of the module<br />please use only lower case letters",
                            "events" => $addModuleEvents
                        ))
                        ->input("Textbox", array(
                            "label" => "title",
                            "help"  => "the title of the module<br />This title is shown everywhere in the cms",
                            "events" => $addModuleEvents
                        ))
                        ->input("Textbox", array(
                            "label" => "category",
                            "help"  => "the category in which the module is in<br />e.g. content elements, security, performance, data, management, styling",
                            "events" => $addModuleEvents
                        ))
                        ->input("Textbox", array(
                            "label" => "dependencies",
                            "help"  => "other modules which this modules requires from",
                            "events" => $addModuleEvents
                        ))
                        ->input("Textbox", array(
                            "label" => "version",
                            "help"  => "the current version of the module<br />e.g. 1.2, 0.4 beta, 5.7, 3",
                            "events" => $addModuleEvents
                        ))
                        ->input("Radiobutton", array(
                            "label" => "type",
                            "data" => array(
                                array("id" => "Module"),
                                array("id" => "FrontendModule")
                            ),
                            "value" => "Module",
                            "events" => $addModuleEvents
                        ))
                        ->input("Textbox", array(
                            "label" => "author",
                            "events" => $addModuleEvents
                        ))
                        ->input("Textbox", array(
                            "label" => "resources",
                            "events" => $addModuleEvents
                        ))
                        ->end()
                    ->startGroup("LazyTitledGroup", array(
                        "title" => "file contents",
                        "groupEvents" => true,
                        "events" => $addModuleEvents
                    ))
                        ->startGroup("LazyTitledGroup", array(
                            "title" => "Module.php",
                            "groupEvents" => true,
                            "events" => $addModuleEvents
                        ))
                            ->startGroup("LazyTitledGroup", array(
                                "title" => "install",
                                "groupEvents" => true,
                                "events" => $addModuleEvents
                            ))
                                ->input("Codearea", array(
                                    "infoKey" => "code_install",
                                    "mode" => "php",
                                    "events" => $addModuleEvents
                                ))
                                ->end()
                            ->startGroup("LazyTitledGroup", array(
                                "title" => "uninstall",
                                "groupEvents" => true,
                                "events" => $addModuleEvents
                            ))
                                ->input("Codearea", array(
                                    "infoKey" => "code_uninstall",
                                    "mode" => "php",
                                    "events" => $addModuleEvents
                                ))
                                ->end()
                            ->startGroup("LazyTitledGroup", array(
                                "title" => "init",
                                "groupEvents" => true,
                                "events" => $addModuleEvents
                            ))
                                ->input("Codearea", array(
                                    "infoKey" => "code_init",
                                    "mode" => "php",
                                    "events" => $addModuleEvents
                                ))
                                ->end()
                            ->end()
                        ->end()
                    ->startGroup("LazyTitledGroup", array(
                        "title" => "events",
                        "groupEvents" => true,
                        "events" => $addModuleEvents
                    ))
                        ->startGroup("LazyAddGroup", array(
                            "groupEvents" => true,
                            "group" => array(
                                "groupEvents" => "events"
                            ),
                            "events" => $addModuleEvents
                        ))
                            ->startGroup("Section", array(
                                "groupEvents" => true,
                                "events" => $addModuleEvents
                            ))
                                ->input("Textbox", array(
                                    "label" => "permissions",
                                    "events" => $addModuleEvents
                                ))
                                ->input("Textbox", array(
                                    "label" => "event",
                                    "events" => $addModuleEvents
                                ))
                                ->input("Checkbox", array(
                                    "label" => "only if logged in",
                                    "value" => true,
                                    "events" => $addModuleEvents
                                ))
                                ->input("Codearea", array(
                                    "label" => "code",
                                    "mode" => "php",
                                    "events" => $addModuleEvents
                                ))
                                ->end();
        }
        
                            
                            
                    
    }
    
    public function menu() {
        return array(
            "development->create module" => "New module"
        );
    }
}

?>
