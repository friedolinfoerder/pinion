<?php
/**
 * This abstract class is an extension of the class Module.
 * If you create an Module, which extends the class FrontendModule, the module
 * can be rendered.
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

use \pinion\events\Event;


abstract class FrontendModule extends Module implements Renderer {
    
    /**
     * Sets listener for the module
     * Make sure to check the permissions, before setting a listener
     * 
     * @return null
     */
    public function addListener() {
        parent::addListener();
        
        $this
            ->addEventListener("getInfos", "getPermissions")
            ->addEventListener("getInfos", "isVisible");
        
        if($this->hasPermission("editor")) {
            $this->addEventListener("defineEditor");
        }
        if($this->hasPermission("add")) {
            $this->addEventListener("add");
        }
        if($this->hasPermission("edit")) {
            $this->addEventListener("edit");
        } elseif($this->hasPermission("edit own")) {
            $this->addEventListener("edit", "editOwn");
        }          
        if($this->hasPermission("add existing")) {
            $this->addEventListener("getContents");
        } elseif($this->hasPermission("add existing of own")) {
            $this->addEventListener("getContents", "getContentsOfOwn");
        }
        $this->addEventListener("getPreview");
    }
    
    /**
     * Get the resources for the module
     * 
     * @return array An array with all resources for the module 
     */
    public function getResources() {
        $resources = parent::getResources();
        
        return array_merge($resources, array(
            "editor",
            "add",
            "add existing",
            "add existing of own",
            "edit",
            "edit own",
            "delete",
            "delete own",
            "change visibility",
            "change visibility of own",
            "change style",
            "change style of own",
            "change assignment",
            "change assignment of own"
        ));
    }
    
    /**
     * The EventListener add
     * 
     * @param Event $event The event, which is injected by the EventDispatcher
     * 
     * @throws \Exception 
     */
    public function add(Event $event) {
        throw new \Exception("The FrontendModule '$this->name' does not implement the function add!");
    }
    
    /**
     * The EventListener edit
     * 
     * @param Event $event The event, which is injected by the EventDispatcher
     * 
     * @throws \Exception 
     */
    public function edit(Event $event) {
        throw new \Exception("The FrontendModule '$this->name' does not implement the function edit!");
    }
    
    /**
     * The EventListener editOwn
     * 
     * @param Event $event The event, which is injected by the EventDispatcher
     */
    public function editOwn(Event $event) {
        $data = $this->data->find($this->name, $event->getInfo("id"));
        if($data->isOwn()) {
            $this->dispatchEvent("edit", $event->getInfo());
        }
    }
    
    /**
     * Set all template variables
     * 
     * @param $data The current data of the module
     * 
     * @return array All properties which are used to render the object 
     */
    public function setFrontendVars($data) {
        return $data->attributes();
    }
    
    
    /**
     * The EventListener getContents
     * 
     * @param Event $event The event, which is injected by the EventDispatcher
     */
    public function getContents(Event $event) {
        
        $start = $event->getInfo("start");
        $end   = $event->getInfo("end");
        
        $previewData = array();
        $data = $this->data->all($this->name, array("offset" => $start, "limit" => $end - $start, "order" => "created DESC"));
        foreach($data as $d) {
            $previewData[] = $this->preview($d);
        }
        $this->response->setInfo("data", $previewData);
        
        if($start == 0) {
            $this->data->count($this->name);
        }
    }
    
    /**
     * The EventListener getContentsOfOwn
     * 
     * @param Event $event The event, which is injected by the EventDispatcher
     */
    public function getContentsOfOwn(Event $event) {
        
        $start = $event->getInfo("start");
        $end   = $event->getInfo("end");
        
        $previewData = array();
        $data = $this->data->all($this->name, array("conditions" => array("user_id = ?", $this->identity->userid), "offset" => $start, "limit" => $end - $start, "order" => "created DESC"));
        foreach($data as $d) {
            $previewData[] = $this->preview($d);
        }
        $this->response->setInfo("data", $previewData);
        
        if($start == 0) {
            $this->data->count($this->name);
        }
    }
    
    /**
     * The EventListener getPreview
     * 
     * @param Event $event The event, which is injected by the EventDispatcher
     */
    public function getPreview(Event $event) {
        $id = $event->getInfo("id");
        $data = $this->data->find($this->name, $id);
        
        $this->response->setInfo("data", $this->preview($data));
    }
    
    /**
     * Set the data for the preview
     * 
     * @param ActiveRecord $data The data of the module
     * 
     * @return array An array with attributes, which were needed to create a preview
     */
    public function preview($data) {
        return $data->attributes();
    }
    
    /**
     * Define the editor
     * 
     * @param Event $event The event, which is injected by the EventDispatcher
     */
    public function defineEditor(Event $event) {
        $this->framework
            ->key("elements")
                ->html("SimpleHtml", "Please override the function \"defineEditor\".");
    }
    
    /**
     * Define the backend
     */
    public function defineBackend() {
        parent::defineBackend();
        
        if($this->hasPermission("add")) {
            $this->framework
                ->startGroup("LazyMainTab", array("title" => "Add"))
                    ->module(ucfirst($this->name), array(
                        "moduleName" => ucfirst($this->name),
                        "name" => $this->name,
                        "vars" => new \stdClass(),
                        "content" => new \stdClass(),
                        "data" => new \stdClass(),
                        "_isNew" => true
                    ));
        }
        
        
        $this->framework
            ->startGroup("LazyMainTab", array("title" => "Elements"))
                ->list("DataPager", array(
                    "display" => array(
                        "renderer" => array(
                            "name" => "ContentElementRenderer",
                            "events" => array(
                                "event" => "editElement",
                                "module" => "page",
                                "info" => array(
                                    "module" => $this->name
                                )
                            ),
                        ),
                        "scrollable" => false,
                        "groupEvents" => "elements"
                    ),
                    "data" => array(
                        "event"  => "getContentElementsByType",
                        "module" => "page",
                        "info"   => array(
                            "module" => $this->name
                        )
                    )
                ));
    }
}
?>
