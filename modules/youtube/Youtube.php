<?php
/**
 * Module Youtube
 * 
 * PHP version 5.3
 * 
 * @author  Friedolin Förder <friedolin.foerder@pinion-cms.org>
 * @license MIT License
 * @link    http://www.pinion-cms.org/module/youtube
 */

namespace modules\youtube;

use \pinion\modules\Module;
use \pinion\modules\FrontendModule;
use \pinion\events\Event;
use \pinion\general\TemplateBuilder;

class Youtube extends FrontendModule {
    
    
    public function install() {
        $this->data
            ->createDataStorage("youtube", array(
                "videoid" => array("type" => "varchar", "length" => 500, "isNull" => true, "translatable" => false),
                "width"   => array("type" => "int", "isNull" => true),
                "height"  => array("type" => "int", "isNull" => true)
            ));
    }
    
    public function defineBackend() {
        parent::defineBackend();
        
        $this->framework
            ->text("Headline", array("text" => "This is the backend area of the module \"Youtube\""));
            
    }
    
    
    public function add(Event $event) {
        
        // the new data
        $data = array();
        

        // fill the data with information from the event...
        $data["videoid"] = $event->getInfo("videoid");
        $data["width"] = $event->getInfo("width", "640");
        $data["height"] = $event->getInfo("height", "390");
        
        
        // create the data storage
        $youtube = $this->data->create($this->name, $data);
        
        
        // give the event and the storage data to the page,
        // so the content can be linked with the page
        $this->module("page")->setModuleContentMapping($event, $youtube);
        
        
        // add a success message
        $this->response->addSuccess($this, $this->translate("%s added", "<b>".$this->translate($this->information["title"])."</b>"));
    }
    
    
    public function edit(Event $event) {
        
        // get the id from the event
        $id = $event->getInfo("id");
        
        
        // get the data storage
        $youtube = $this->data->find($this->name, $id);
        
        
        
        // update the data storage...
        if($event->hasInfo("videoid")) {
            $youtube->videoid = $event->getInfo("videoid");
        }
        if($event->hasInfo("width")) {
            $youtube->width = $event->getInfo("width");
        }
        if($event->hasInfo("height")) {
            $youtube->height = $event->getInfo("height");
        }
        
        
        // save the data storage
        $youtube->save();
        
        
        // dispatch the content event of the page
        // to update the contents on the page
        $this->module("page")->dispatchEvent("content", array("data" => $youtube));
        
        
        // add a success message
        $this->response->addSuccess($this, $this->translate("%s edited", "<b>".$this->translate($this->information["title"])."</b>"));
    }
    
    public function defineEditor(Event $event) {
        $settings = $event->getInfo("settings");
        
        $this->framework
            ->key("elements")
                ->input("Textbox", array(
                    "label" => "id of video",
                    "infoKey" => "videoid",
                    "value" => $settings["_isNew"] ? "" : $settings["data"]["videoid"],
                    "events" => $settings["events"],
                    "validators" => array(
                        "notEmpty" => true
                    )
                ))
                ->input("Slider", array(
                    "label" => "width",
                    "min" => 120,
                    "max" => 1920,
                    "value" => ($settings["_isNew"] || !$settings["data"]["width"]) ? 640 : $settings["data"]["width"],
                    "events" => $settings["events"]
                ))
                ->input("Slider", array(
                    "label" => "height",
                    "min" => 90,
                    "max" => 1200,
                    "value" => ($settings["_isNew"] || !$settings["data"]["height"]) ? 390 : $settings["data"]["height"],
                    "events" => $settings["events"]
                ));
                
    }
}


?>