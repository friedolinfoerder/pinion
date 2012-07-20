<?php
/**
 * Module Code
 * 
 * PHP version 5.3
 * 
 * @author  Friedolin Förder <friedolin.foerder@pinion-cms.org>
 * @license MIT License
 * @link    http://www.pinion-cms.org/module/code
 */

namespace modules\code;

use \pinion\modules\Module,
    \pinion\modules\FrontendModule,
    \pinion\events\Event,
    \pinion\general\TemplateBuilder;

class Code extends FrontendModule {
    
    public function install() {
        $this->data
            ->createDataStorage("code", array(
                "code" => array("type" => "text", "translatable" => false)
            ));
    }
    
    public function getResources() {
        return array_merge(parent::getResources(), array(
            "allow php",
            "allow tags"
        ));
    }
    
    public function setFrontendVars($data) {
        return $data->attributes(array(
            "*",
            "code" => function($value) {
                ob_start();
                eval("?>".$value."<?php ;");
                return ob_get_clean();
            }
        ));
    }
    
    public function add(Event $event) {
        
        // the new data
        $data = array();
        
        
        
        // fill the data with information from the event...
        $data["code"] = $event->getInfo("code");
        
        if(!$this->hasPermission("allow tags")) {
            $data["code"] = strip_tags($data["code"]);
        }
        if(!$this->hasPermission("allow php")) {
            $data["code"] = preg_replace(array("/<(\?|\%)\=?(php)?/", "/(\%|\?)>/"), array("", ""), $data["code"]);
        }
        
        
        // create the data storage
        $code = $this->data->create($this->name, $data);
        
        
        // give the event and the storage data to the page,
        // so the content can be linked with the page
        $this->module("page")->setModuleContentMapping($event, $code);
        
        
        // add a success message
        $this->response->addSuccess($this, $this->translate("%s added", "<b>".$this->translate($this->information["title"])."</b>"));
    }
    
    
    public function edit(Event $event) {
        
        // get the id from the event
        $id = $event->getInfo("id");
        
        
        // get the data storage
        $code = $this->data->find($this->name, $id);
        
        
        
        // update the data storage...
        $codeString = $event->getInfo("code");
        
        if(!$this->hasPermission("allow tags")) {
            $codeString = strip_tags($codeString);
        }
        if(!$this->hasPermission("allow php")) {
            $codeString = preg_replace(array("/<(\?|\%)\=?(php)?/", "/(\%|\?)>/"), array("", ""), $codeString);
        }
        $code->code = $codeString;
        
        
        // save the data storage
        $code->save();
        
        
        // dispatch the content event of the page
        // to update the contents on the page
        $this->module("page")->dispatchEvent("content", array("data" => $code));
        
        
        // add a success message
        $this->response->addSuccess($this, $this->translate("%s edited", "<b>".$this->translate($this->information["title"])."</b>"));
    }
    
    public function defineEditor(Event $event) {
        
        $settings = $event->getInfo("settings");
        
        $this->framework
            ->key("elements")
                ->input("Codearea", array(
                    "infoKey" => "code",
                    "value" => $settings["_isNew"] ? "" : $settings["data"]["code"],
                    "events" => $settings["events"] 
                ));
    }
}


?>