<?php
/**
 * Module Link
 * 
 * PHP version 5.3
 * 
 * @author  Friedolin Förder <friedolin.foerder@pinion-cms.org>
 * @license MIT License
 * @link    http://www.pinion-cms.org/module/link
 */

namespace modules\link;

use \pinion\modules\Module;
use \pinion\modules\FrontendModule;
use \pinion\events\Event;
use \pinion\general\TemplateBuilder;
use \pinion\general\Registry;

class Link extends FrontendModule {
    
    
    public function install() {
        $this->data
            ->createDataStorage("link", array(
                "title"  => array("type" => "varchar", "length" => 100),
                "url"    => array("type" => "varchar", "length" => 100),
                "newtab" => array("type" => "boolean"),
                "page"   => "page"
            ));
    }
    
    
    public function defineBackend() {
        parent::defineBackend();
        
        $this->framework
            ->text("Headline", array("text" => "This is the backend area of the module \"Link\""));
            
    }
    
    
    public function add(Event $event) {
        
        // the new data
        $data = array();
        
        
        
        // fill the data with information from the event...
        $data["newtab"] = $event->hasInfo("newTab");
        $url = $event->getInfo("url");
        $data["title"] = $event->getInfo("title", "");
        $page = $this->module("page")->data->find_by_url("page", $url);
        if(is_object($page)) {
            $data["page_id"] = $page->id;
        }
        $data["url"] = $url;
        
        
        
        // create the data storage
        $link = $this->data->create($this->name, $data);
        
        
        // give the event and the storage data to the page,
        // so the content can be linked with the page
        $this->module("page")->setModuleContentMapping($event, $link);
        
        
        // add a success message
        $this->response->addSuccess($this, $this->translate("%s added", "<b>".$this->translate($this->information["title"])."</b>"));
    }
    
    
    public function edit(Event $event) {
        
        // get the id from the event
        $id = $event->getInfo("id");
        
        
        // get the data storage
        $link = $this->data->find($this->name, $id);
        
        
        
        // update the data storage...
        if($event->hasInfo("newTab")) {
            $link->newtab = $event->getInfo("newTab");
        }
        if($event->hasInfo("title")) {
            $title = $event->getInfo("title");
            if(is_string($title)) {
                $title = trim($event->getInfo("title"));
            }
            $link->title = $title;
        }
        if($event->hasInfo("url")) {
            $url = $event->getInfo("url");
            $page = $this->module("page")->data->find_by_url("page", $url);
            if(is_object($page)) {
                $link->page_id = $page->id;
            } 
            $link->url = $url;
        }
        
        
        // save the data storage
        $link->save();
        
        
        // dispatch the content event of the page
        // to update the contents on the page
        $this->module("page")->dispatchEvent("content", array("data" => $link));
        
        
        // add a success message
        $this->response->addSuccess($this, $this->translate("%s edited", "<b>".$this->translate($this->information["title"])."</b>"));
    }
    
    public function setFrontendVars($data) {
        return array(
            "id" => $data->id,
            "url" => is_null($data->page_id) ? $data->url : SITE_URL.$data->page->url,
            "title" => $data->title == "" ? (is_null($data->page_id) ? $data->url : ($data->page->title ?: $data->url)) : $data->title,
            "onNewTab" => $data->newtab,
            "newTab" => $data->newtab ? " target='_blank'" : ""
        );
    }
    
    public function preview($data) {
        return $this->setFrontendVars($data);
    }
    
    public function defineEditor(Event $event) {
        $settings = $event->getInfo("settings");
        
        if(count(Registry::getSupportedLanguages()) == 1 && ($settings["_isNew"] || is_string($settings["data"]["title"]))) {
            $editor = "Textbox";
        } else {
            $editor = "TranslationTextbox";
        }
        
        $this->framework
            ->key("elements")
                ->input("AutoCompleteTextbox", array(
                    "value" => $settings["_isNew"] ? "" : ($settings["data"]["page_id"] ? $this->module("page")->data->find("page", $settings["data"]["page_id"])->url : $settings["data"]["url"]),
                    "data" => array(
                        "event" => "getUrlData",
                        "module" => "page"
                    ),
                    "label" => "url",
                    "validators" => array(
                        "notEmpty" => true
                    ),
                    "events" => $settings["events"]
                ))
                ->input($editor, array(
                    "value" => $settings["_isNew"] ? "" : $settings["data"]["title"],
                    "label" => "title",
                    "events" => $settings["events"]
                ))
                ->input("Checkbox", array(
                    "value" => $settings["_isNew"] ? false : $settings["data"]["newtab"],
                    "label" => "open in new tab",
                    "infoKey" => "newTab",
                    "events" => $settings["events"]
                ));
    }
        
}


?>