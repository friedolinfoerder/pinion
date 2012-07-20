<?php
/**
 * Module Headline
 * 
 * PHP version 5.3
 * 
 * @author  Friedolin Förder <friedolin.foerder@pinion-cms.org>
 * @license MIT License
 * @link    http://www.pinion-cms.org/module/headline
 */

namespace modules\headline;

use \pinion\modules\FrontendModule;
use \pinion\events\Event;
use \pinion\general\Registry;


class Headline extends FrontendModule {
    
    public function install() {
        $this->data
            ->createDataStorage("headline", array(
                "text" => array("type" => "text"),
                "size" => array("type" => "int")
            ));
    }
    
    public function getResources() {
        return array_merge(parent::getResources(), array(
            "allow bold and italic (b & i-tag)"
        ));
    }
    
    public function addListener() {
        parent::addListener();
        
        $this->addEventListener("search");
    }
    
    public function add(Event $event) {
        
        $filter = $this->hasPermission("allow bold and italic (b & i-tag)") ? "<b><strong><i><em>" : null;
        $text = $event->getInfo("text");
        if(is_string($text)) {
            strip_tags($text, $filter);
        } elseif(is_array($text)) {
            foreach($text as &$t) {
                $t = strip_tags($t, $filter);
            }
        }
        $headline = $this->data->create("headline", array(
            "text" => $text,
            "size" => $event->getInfo("size") ?: 1
        ));
        
        $this->module("page")->setModuleContentMapping($event, $headline);
        
        
        // add success message
        $this->response->addSuccess($this, $this->translate("%s added", "<b>".$this->translate($this->information["title"])."</b>"));
    }
    
    public function edit(Event $event) {
        
        $headline = $this->data->find("headline", $event->getInfo("id"));
        
        if($event->hasInfo("text")) {
            $filter = $this->hasPermission("allow bold and italic (b & i-tag)") ? "<b><strong><i><em>" : null;
            $text = $event->getInfo("text");
            if(is_string($text)) {
                strip_tags($text, $filter);
            } elseif(is_array($text)) {
                foreach($text as &$t) {
                    $t = strip_tags($t, $filter);
                }
            }
            $headline->text = $text;
        }
        if($event->hasInfo("size")) {
            $headline->size = $event->getInfo("size");
        }
        $headline->save();
        
        // dispatch content event to update the contents on the page
        $this->module("page")->dispatchEvent("content", array("data" => $headline));
        
        // add success message
        $this->response->addSuccess($this, $this->translate("%s edited", "<b>".$this->translate($this->information["title"])."</b>"));
    }
    
    public function setFrontendVars($data) {
        
        $text = $data->text;
        $size = $data->size;
        $tag = "h$size";
        $openTag = "<$tag>";
        $closeTag = "</$tag>";
        
        return array(
            "text" => $text,
            "size" => $size,
            "tag"  => $tag,
            "openTag" => $openTag,
            "closeTag" => $closeTag,
            "element" => $openTag.$text.$closeTag
        );
    }
    
    public function preview($data) {
        return $data->attributes(array(
            "*",
            "text"
        ));
    }
    
    public function search(Event $event) {
        $value = $event->getInfo("value");
        return $this->data->all("headline", array("conditions" => array("text LIKE ?", '%'.$value.'%')));
    }
    
    public function defineEditor(Event $event) {
        $settings = $event->getInfo("settings");
        
        if(count(Registry::getSupportedLanguages()) == 1 && ($settings["_isNew"] || is_string($settings["data"]["text"]))) {
            $editor = "Textbox";
            $text = $settings["_isNew"] ? "" : $settings["data"]["text"];
        } else {
            $editor = "TranslationTextbox";
            $text = $settings["_isNew"] ? "" : $this->data->find("headline", $settings["data"]["id"])->text;
        }
        
        $this->framework
            ->key("elements")
                ->input($editor, array(
                    "value" => $text,
                    "infoKey" => "text",
                    "events" => $settings["events"]
                ))
                ->list("Selector", array(
                    "infoKey" => "size",
                    "noEmptyValue" => true,
                    "value" => $settings["_isNew"] ? 1 : $settings["data"]["size"],
                    "data" => array(
                        array("id" => 1, "value" => "h1"),
                        array("id" => 2, "value" => "h2"),
                        array("id" => 3, "value" => "h3"),
                        array("id" => 4, "value" => "h4"),
                        array("id" => 5, "value" => "h5"),
                        array("id" => 6, "value" => "h6")
                    ),
                    "events" => $settings["events"]
                ));
    }
}

?>
