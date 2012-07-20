<?php
/**
 * Module Textfield
 * 
 * PHP version 5.3
 * 
 * @author  Friedolin Förder <friedolin.foerder@pinion-cms.org>
 * @license MIT License
 * @link    http://www.pinion-cms.org/module/textfield
 */

namespace modules\textfield;

use \pinion\modules\FrontendModule;
use \pinion\events\Event;
use \pinion\general\Registry;

class Textfield extends FrontendModule {
    
    public function install() {
        $this->data
            ->createDataStorage("textfield", array(
                "text" => array("type" => "text")
            ));
    }
    
    public function addListener() {
        parent::addListener();
        
        $this->addEventListener("search");
    }
    
    public function search(Event $event) {
        $value = $event->getInfo("value");
        return $this->data->all("textfield", array("conditions" => array("text LIKE ?", '%'.$value.'%')));
    }
    
    public function edit(Event $event) {
        $id = $event->getInfo("id");
        $text = $event->getInfo("text");
        
        $textfield = $this->data->find("textfield", $id);
        $textfield->text = $text;
        $textfield->save();
        
        // dispatch content event to update the contents on the page
        $this->module("page")->dispatchEvent("content", array("data" => $textfield));
        
        // add success message
        $this->response->addSuccess($this, $this->translate("%s edited", "<b>".$this->translate($this->information["title"])."</b>"));
    }
    
    public function remove(Event $event) {
        $id = $event->getInfo("moduleid");
        
        $this->deleteData("textfield", $id);
    }

    public function add(Event $event) {
        
        $text = $event->getInfo("text");
        
        $textfield = $this->data->create("textfield", array(
            "text" => $text
        ));
        
        $this->module("page")->setModuleContentMapping($event, $textfield);
        
        
        // add success message
        $this->response->addSuccess($this, $this->translate("%s added", "<b>".$this->translate($this->information["title"])."</b>"));
    }

    public function setFrontendVars($data) {
        
        return $data->attributes(array("text"));
    }
    
    public function preview($data) {
        return $data->attributes(array(
            "*",
            "text"
        ));
    }
    
    public function defineBackend() {
        parent::defineBackend();
    }
    
    public function defineEditor(Event $event) {
        $settings = $event->getInfo("settings");
        
        if(count(Registry::getSupportedLanguages()) == 1 && ($settings["_isNew"] || is_string($settings["data"]["text"]))) {
            $editor = "Editor";
            $text = $settings["_isNew"] ? "" : $settings["data"]["text"];
        } else {
            $editor = "TranslationEditor";
            $text = $settings["_isNew"] ? "" : $this->data->find("textfield", $settings["data"]["id"])->text;
        }
        
        $this->framework
            ->key("elements")
                ->input($editor, array(
                    "value" => $settings["_isNew"] ? "" : $text,
                    "CKOptions" => array(
                        "toolbar" => "Frontend"
                    ),
                    "infoKey" => "text",
                    "events" => $settings["events"]
                ));
        
    }
}
?>
