<?php
/**
 * Module Todo
 * 
 * PHP version 5.3
 * 
 * @author  Friedolin Förder <friedolin.foerder@pinion-cms.org>
 * @license MIT License
 * @link    http://www.pinion-cms.org/module/todo
 */

namespace modules\todo;

use \pinion\modules\Module;
use \pinion\modules\FrontendModule;
use \pinion\events\Event;
use \pinion\general\TemplateBuilder;

class Todo extends FrontendModule {
    
    
    public function install() {
        $this->data
            ->createDataStorage("todo", array(
                "text" => array("type" => "text")
            ));
    }
    
    public function getResources() {
        
        $resources = parent::getResources();
        
        unset(
            $resources[array_search("change visibility", $resources)],
            $resources[array_search("change visibility of own", $resources)]
        );
        
        return $resources;
    }
    
    public function add(Event $event) {
        
        // the new data
        $data = array();
        
        
        
        // fill the data with information from the event...
        $data["text"] = $event->getInfo("text");
        
        
        // create the data storage
        $todo = $this->data->create($this->name, $data);
        
        
        // give the event and the storage data to the page,
        // so the content can be linked with the page
        $this->module("page")->setModuleContentMapping($event, $todo);
        
        
        // add a success message
        $this->response->addSuccess($this, $this->translate("%s added", "<b>".$this->translate($this->information["title"])."</b>"));
    }
    
    
    public function edit(Event $event) {
        
        // get the id from the event
        $id = $event->getInfo("id");
        
        
        // get the data storage
        $todo = $this->data->find($this->name, $id);
        
        
        
        // update the data storage...
        $todo->text = $event->getInfo("text");
        
        
        // save the data storage
        $todo->save();
        
        
        // dispatch the content event of the page
        // to update the contents on the page
        $this->module("page")->dispatchEvent("content", array("data" => $todo));
        
        
        // add a success message
        $this->response->addSuccess($this, $this->translate("%s edited", "<b>".$this->translate($this->information["title"])."</b>"));
    }
    
    public function defineEditor(Event $event) {
        $settings = $event->getInfo("settings");
        
        $this->framework
            ->key("elements")
                ->input("Editor", array(
                    "value" => $settings["_isNew"] ? "" : $settings["data"]["text"],
                    "CKOptions" => array(
                        "toolbar" => "Frontend"
                    ),
                    "infoKey" => "text",
                    "events" => $settings["events"]
                ));
        
    }
        
}


?>