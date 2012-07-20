<?php
/**
 * Module Facebooklike
 * 
 * PHP version 5.3
 * 
 * @author  Friedolin Förder <friedolin.foerder@pinion-cms.org>
 * @license MIT License
 * @link    http://www.pinion-cms.org/module/facebooklike
 */

namespace modules\facebooklike;

use \pinion\modules\Module;
use \pinion\modules\FrontendModule;
use \pinion\events\Event;
use \pinion\general\TemplateBuilder;

class Facebooklike extends FrontendModule {
    
    protected $_first = true;
    
    public function install() {
        $this->data
            ->createDataStorage("facebooklike", array(
                // tab 1
                "width" => array("type" => "int"),
                "layout" => array("type" => "varchar", "length" => 20, "isNull" => true, "translatable" => false),
                "has_send" => array("type" => "boolean"),
                "has_face" => array("type" => "boolean"),
                "is_light" => array("type" => "boolean"),
                "like" => array("type" => "boolean"),
                "font" => array("type" => "varchar", "length" => 20, "isNull" => true, "translatable" => false),
                // tab 2
                "appid" => array("type" => "varchar", "length" => 500, "translatable" => false),
                "type" => array("type" => "varchar", "length" => 100, "translatable" => false),
                "title" => array("type" => "varchar", "length" => 100, "isNull" => true, "translatable" => false),
                "url"   => array("type" => "varchar", "length" => 100, "isNull" => true, "translatable" => false),
                "image" => array("type" => "varchar", "length" => 100, "isNull" => true, "translatable" => false),
                "sitename" => array("type" => "varchar", "length" => 100, "isNull" => true)
            ));
    }
    
    
    public function add(Event $event) {
        
        // the new data
        $data = array();
        
        
        
        // fill the data with information from the event...
        $data = array(
            // tab 1
            "width" => $event->getInfo("width", 450),
            "layout" => $event->getInfo("layout"),
            "has_send" => $event->getInfo("has_send", true),
            "has_face" => $event->getInfo("has_face", true),
            "is_light" => !$event->hasInfo("is_light"),
            "like" => !$event->hasInfo("like"),
            "font" => $event->getInfo("font"),
            // tab 2
            "appid" => $event->getInfo("appid"),
            "type" => $event->getInfo("type", ""),
            "title" => $event->getInfo("title", ""),
            "url" => $event->getInfo("url", ""),
            "image" => $event->getInfo("image", ""),
            "sitename" => $event->getInfo("sitename", "")
        );
        
        
        // create the data storage
        $facebooklike = $this->data->create($this->name, $data);
        
        
        // give the event and the storage data to the page,
        // so the content can be linked with the page
        $this->module("page")->setModuleContentMapping($event, $facebooklike);
        
        
        // add a success message
        $this->response->addSuccess($this, $this->translate("%s added", "<b>".$this->translate($this->information["title"])."</b>"));
    }
    
    
    public function edit(Event $event) {
        
        // get the id from the event
        $id = $event->getInfo("id");
        
        
        // get the data storage
        $facebooklike = $this->data->find($this->name, $id);
        
        
        
        // update the data storage...
        // tab 1
        if($event->hasInfo("width")) {
            $facebooklike->width = $event->getInfo("width");
        }
        if($event->hasInfo("layout")) {
            $layout = $event->getInfo("layout");
            $facebooklike->size = $layout == "standard" ? null : $layout;
        }
        if($event->hasInfo("has_send")) {
            $facebooklike->has_send = $event->getInfo("has_send");
        }
        if($event->hasInfo("has_face")) {
            $facebooklike->has_face = $event->getInfo("has_face");
        }
        if($event->hasInfo("is_light")) {
            $facebooklike->is_light = $event->getInfo("is_light") == "dark" ? false : true;
        }
        if($event->hasInfo("like")) {
            $facebooklike->font = $event->getInfo("like") == "like" ? true : false;
        }
        if($event->hasInfo("font")) {
            $facebooklike->font = $event->getInfo("font") == "" ? null : $event->getInfo("font");
        }
        // tab 2
        if($event->hasInfo("appid")) {
            $facebooklike->appid = $event->getInfo("appid");
        }
        if($event->hasInfo("type")) {
            $facebooklike->type = $event->getInfo("type");
        }
        if($event->hasInfo("title")) {
            $facebooklike->title = $event->getInfo("title");
        }
        if($event->hasInfo("url")) {
            $facebooklike->url = $event->getInfo("url");
        }
        if($event->hasInfo("image")) {
            $facebooklike->image = $event->getInfo("image");
        }
        if($event->hasInfo("sitename")) {
            $facebooklike->sitename = $event->getInfo("sitename");
        }
        
        
        // save the data storage
        $facebooklike->save();
        
        
        // dispatch the content event of the page
        // to update the contents on the page
        $this->module("page")->dispatchEvent("content", array("data" => $facebooklike));
        
        
        // add a success message
        $this->response->addSuccess($this, $this->translate("%s edited", "<b>".$this->translate($this->information["title"])."</b>"));
    }
    
    public function setFrontendVars($data) {
        $attributes = array("class=\"fb-like\"");
        
        $attributes[] = "data-width=\"".$data->width."\"";
        
        if($data->layout) {
            $attributes[] = "data-layout=\"".$data->layout."\"";
        }
        if($data->font) {
            $attributes[] = "data-font=\"".$data->font."\"";
        }
        if(!$data->is_light) {
            $attributes[] = "data-colorscheme=\"dark\"";
        }
        if(!$data->like) {
            $attributes[] = "data-action=\"recommend\"";
        }
        $attributes[] = "data-send=\"".($data->has_send ? "true" : "false")."\"";
        $attributes[] = "data-show-faces=\"".($data->has_face ? "true" : "false")."\"";
        
        $attributes = join(" ", $attributes);
        
        $return = $data->attributes();
        $return["attributes"] = $attributes;
        $return["first"] = $this->_first;
        if($this->_first) {
            $this->_first = false;
            
            $return["code"] = "<div id='fb-root'></div>";
        }
        
        // add meta data
        $this->response->addMeta("property='fb:admins' content='{$data->appid}'");
        
        return $return;
    }
    
    
    public function defineEditor(Event $event) {
        $settings = $event->getInfo("settings");
        
        $this->framework
            ->key("elements")
                ->startGroup("TabGroup", array(
                    "groupEvents" => true
                ))
                    ->startGroup("TitledSection", array(
                        "title" => "code",
                        "groupEvents" => true
                    ))
                        ->input("Checkbox", array(
                            "label" => "Send Button",
                            "infoKey" => "has_send",
                            "value" => $settings["_isNew"] ? true : $settings["data"]["has_send"],
                            "events" => $settings["events"]
                        ))
                        ->list("Selector", array(
                            "label" => "Layout",
                            "infoKey" => "layout",
                            "noEmptyValue" => true,
                            "value" => $settings["_isNew"] ? null : $settings["data"]["layout"],
                            "data" => array(
                                array("id" => "standard"),
                                array("id" => "button_count"),
                                array("id" => "box_count")
                            ),
                            "events" => $settings["events"]
                        ))
                        ->input("Slider", array(
                            "label" => "Width",
                            "infoKey" => "width",
                            "min" => 120,
                            "max" => 1000,
                            "value" => ($settings["_isNew"] || !$settings["data"]["width"]) ? 450 : $settings["data"]["width"],
                            "events" => $settings["events"]
                        ))
                        ->input("Checkbox", array(
                            "label" => "Show Faces",
                            "infoKey" => "has_face",
                            "value" => $settings["_isNew"] ? true : $settings["data"]["has_face"],
                            "events" => $settings["events"]
                        ))
                        ->list("Selector", array(
                            "label" => "Verb to display",
                            "infoKey" => "like",
                            "noEmptyValue" => true,
                            "value" => ($settings["_isNew"] || $settings["data"]["like"]) ? "like" : "recommend",
                            "data" => array(
                                array("id" => "like"),
                                array("id" => "recommend")
                            ),
                            "events" => $settings["events"]
                        ))
                        ->list("Selector", array(
                            "label" => "Color Scheme",
                            "infoKey" => "is_light",
                            "noEmptyValue" => true,
                            "value" => ($settings["_isNew"] || $settings["data"]["is_light"]) ? "light" : "dark",
                            "data" => array(
                                array("id" => "light"),
                                array("id" => "dark")
                            ),
                            "events" => $settings["events"]
                        ))
                        ->list("Selector", array(
                            "label" => "Font",
                            "infoKey" => "is_light",
                            "value" => $settings["_isNew"] ? null : $settings["data"]["font"],
                            "data" => array(
                                array("id" => "arial"),
                                array("id" => "lucida grande"),
                                array("id" => "segoe ui"),
                                array("id" => "tahoma"),
                                array("id" => "trebuchet ms"),
                                array("id" => "verdana")
                            ),
                            "events" => $settings["events"]
                        ))
                        ->end()
                    ->startGroup("TitledSection", array(
                        "title" => "meta tags",
                        "groupEvents" => true
                    ))
                        ->input("Textbox", array(
                            "label" => "Admin",
                            "infoKey" => "appid",
                            "help" => "A comma-separated list of either the Facebook ids of page administrators or a Facebook Platform application id. At a minimum, include only your own Facebook id.",
                            "value" => $settings["_isNew"] ? "" : $settings["data"]["appid"],
                            "validators" => array(
                                "notEmpty" => true
                            ),
                            "events" => $settings["events"]
                        ))
                        ->list("Selector", array(
                            "label" => "Type",
                            "infoKey" => "type",
                            "value" => $settings["_isNew"] ? null : $settings["data"]["type"],
                            "data" => array(
                                array("id" => "activity"),
                                array("id" => "actor"),
                                array("id" => "album"),
                                array("id" => "article"),
                                array("id" => "athlete"),
                                array("id" => "author"),
                                array("id" => "band"),
                                array("id" => "bar"),
                                array("id" => "blog"),
                                array("id" => "book"),
                                array("id" => "cafe"),
                                array("id" => "cause"),
                                array("id" => "city"),
                                array("id" => "company"),
                                array("id" => "country"),
                                array("id" => "director"),
                                array("id" => "drink"),
                                array("id" => "food"),
                                array("id" => "game"),
                                array("id" => "government"),
                                array("id" => "hotel"),
                                array("id" => "landmark"),
                                array("id" => "movie"),
                                array("id" => "musician"),
                                array("id" => "non_profit"),
                                array("id" => "politician"),
                                array("id" => "product"),
                                array("id" => "public_figure"),
                                array("id" => "restaurant"),
                                array("id" => "school"),
                                array("id" => "song"),
                                array("id" => "sport"),
                                array("id" => "sports_league"),
                                array("id" => "sports_team"),
                                array("id" => "state_province"),
                                array("id" => "tv_show"),
                                array("id" => "university"),
                                array("id" => "website")
                            ),
                            "events" => $settings["events"]
                        ))
                        ->input("Textbox", array(
                            "label" => "Url",
                            "infoKey" => "url",
                            "value" => $settings["_isNew"] ? "" : $settings["data"]["url"],
                            "events" => $settings["events"]
                        ))
                        ->input("Textbox", array(
                            "label" => "Image",
                            "infoKey" => "image",
                            "value" => $settings["_isNew"] ? "" : $settings["data"]["image"],
                            "events" => $settings["events"]
                        ))
                        ->input("Textbox", array(
                            "label" => "Site name",
                            "infoKey" => "sitename",
                            "value" => $settings["_isNew"] ? "" : $settings["data"]["sitename"],
                            "events" => $settings["events"]
                        ));
    }
    
    public function preview($data) {
        return $data->attributes(array(
            "*",
            "layout" => function($value) {
                return $value ?: "standard";
            },
            "font" => function($value) {
                return $value ?: "inherit";
            }
        ));
    }
}


?>