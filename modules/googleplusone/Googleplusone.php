<?php
/**
 * Module Googleplusone
 * 
 * PHP version 5.3
 * 
 * @author  Friedolin Förder <friedolin.foerder@pinion-cms.org>
 * @license MIT License
 * @link    http://www.pinion-cms.org/module/googleplusone
 */

namespace modules\googleplusone;

use \pinion\modules\Module;
use \pinion\modules\FrontendModule;
use \pinion\events\Event;
use \pinion\general\TemplateBuilder;

class Googleplusone extends FrontendModule {
    
    
    public function install() {
        $this->data
            ->createDataStorage("googleplusone", array(
                "size"       => array("type" => "varchar", "length" => 10, "translatable" => false, "isNull" => true),
                "width"      => array("type" => "int", "isNull" => true),
                "annotation" => array("type" => "varchar", "length" => 10, "translatable" => false, "isNull" => true)
            ));
    }
    
    public function add(Event $event) {
        
        $googleplus = $this->data->create("googleplusone", array(
            "size" => $event->getInfo("size"),
            "width" => $event->getInfo("width"),
            "annotation" => $event->getInfo("annotation")
        ));
        
        $this->module("page")->setModuleContentMapping($event, $googleplus);
        
        // add success message
        $this->response->addSuccess($this, $this->translate("%s added", "<b>".$this->translate($this->information["title"])."</b>"));
    }
    
    public function edit(Event $event) {
        
        $googleplus = $this->data->find("googleplusone", $event->getInfo("id"));
        
        if($event->hasInfo("size")) {
            $size = $event->getInfo("size");
            $googleplus->size = $size == "standard" ? null : $size;
        }
        if($event->hasInfo("width")) {
            $width = $event->getInfo("width");
            $googleplus->width = $width == 450 ? null : $width;
        }
        if($event->hasInfo("annotation")) {
            $annotation = $event->getInfo("annotation");
            $googleplus->annotation = $annotation == "bubble" ? null : $annotation;
        }
        $googleplus->save();
        
        // dispatch content event to update the contents on the page
        $this->module("page")->dispatchEvent("content", array("data" => $googleplus));
        
        // add success message
        $this->response->addSuccess($this, $this->translate("%s edited", "<b>".$this->translate($this->information["title"])."</b>"));
    }
    
    public function setFrontendVars($data) {
        $attributes = array("class=\"g-plusone\"");
        
        if($data->size) {
            $attributes[] = "data-size=\"".$data->size."\"";
        }
        if($data->width) {
            $attributes[] = "data-width=\"".$data->width."\"";
        }
        if($data->annotation) {
            $attributes[] = "data-annotation=\"".$data->annotation."\"";
        }
        
        $attributes = join(" ", $attributes);
        
        return $data->attributes(array(
            "*",
            "attributes" => $attributes,
            "lang" => $this->session->getParameter("lang")
        ));
    }
    
    public function defineBackend() {
        parent::defineBackend();
    }
    
    public function defineEditor(Event $event) {
        $settings = $event->getInfo("settings");
        
        $this->framework
            ->key("elements")
                ->list("Selector", array(
                    "label" => "size",
                    "value" => $settings["_isNew"] ? null : $settings["data"]["size"],
                    "data" => array(
                        array("id" => "standard"),
                        array("id" => "small"),
                        array("id" => "medium"),
                        array("id" => "tall")
                    ),
                    "events" => $settings["events"]
                ))
                ->list("Selector", array(
                    "label" => "annotation",
                    "noEmptyValue" => true,
                    "value" => $settings["_isNew"] ? null : $settings["data"]["annotation"],
                    "data" => array(
                        array("id" => "bubble"),
                        array("id" => "inline"),
                        array("id" => "none")
                    ),
                    "events" => $settings["events"]
                ))
                ->input("Slider", array(
                    "label" => "width",
                    "min" => 120,
                    "max" => 1000,
                    "value" => ($settings["_isNew"] || !$settings["data"]["width"]) ? 450 : $settings["data"]["width"],
                    "events" => $settings["events"]
                ));
    }
    
    public function preview($data) {
        return $data->attributes(array(
            "*",
            "width" => function($value) {
                return $value ?: 450;
            },
            "size" => function($value) {
                return $value ?: "standard";
            },
            "annotation" => function($value) {
                return $value ?: "bubble";
            }
        ));
    }
}


?>