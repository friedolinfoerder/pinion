<?php
/**
 * Module Comment
 * 
 * PHP version 5.3
 * 
 * @author  Friedolin Förder <friedolin.foerder@pinion-cms.org>
 * @license MIT License
 * @link    http://www.pinion-cms.org/module/comment
 */

namespace modules\comment;

use \pinion\modules\Module;
use \pinion\modules\FrontendModule;
use \pinion\events\Event;

class Comment extends FrontendModule {
    
    
    public function install() {
        
        $this->data
            ->createDataStorage("comment", array(
                "has_email"    => array("type" => "boolean"),
                "has_homepage" => array("type" => "boolean"),
                "has_subject"  => array("type" => "boolean")
            ))
            ->createDataStorage("text", array(
                "name"     => array("type" => "varchar", "length" => 100, "translatable" => false),
                "email"    => array("type" => "varchar", "length" => 100, "translatable" => false, "isNull" => true),
                "homepage" => array("type" => "varchar", "length" => 100, "translatable" => false, "isNull" => true),
                "subject"  => array("type" => "varchar", "length" => 100, "translatable" => false, "isNull" => true),
                "text"     => array("type" => "text", "translatable" => false),
                "status"   => array("type" => "int"),
                "comment"
            ));
    }
    
    public function getResources() {
        return array_merge(parent::getResources(), array(
            "approve comment",
            "delete comment"
        ));
    }
    
    public function addListener() {
        parent::addListener();
        
        $this->addEventListener("addComment");
        
        if($this->identity) {
            $this->addEventListener("editComment");
        }
    }
    
    public function add(Event $event) {
        $comment = $this->data->create("comment", array(
            "has_email" => $event->hasInfo("has_email"),
            "has_homepage" => $event->hasInfo("has_homepage"),
            "has_subject" => $event->hasInfo("has_subject")
        ));
        
        $this->module("page")->setModuleContentMapping($event, $comment);
        
        
        // add success message
        $this->response->addSuccess($this, $this->translate("%s added", "<b>".$this->translate($this->information["title"])."</b>"));
    }
    
    public function edit(Event $event) {
        $id = $event->getInfo("id");
        
        $data = $this->data->find("comment", $id);
        $data->update_attributes($event->getInfo());
        
        $this->module("page")->dispatchEvent("content", array("data" => $data));
        
        // add success message
        $this->response->addSuccess($this, $this->translate("%s edited", "<b>".$this->translate($this->information["title"])."</b>"));
    }
    
    public function editComment(Event $event) {
        $comments = $event->getInfo("comments");
        
        foreach($comments as $comment) {
            $id = $comment["id"];
            if(isset($comment["deleted"]) && $comment["deleted"] == true) {
                if($this->hasPermission("delete comment")) {
                    $this->data->find("text", $id)->delete();
                }
            } else {
                if($this->hasPermission("approve comment")) {
                    $data = $this->data->find("text", $id);
                    $data->status = 2;
                    $data->save();
                }
            }
        }
        
        // add success message
        $this->response->addSuccess($this, $this->translate("comments were activated", "<b>".$this->translate($this->information["title"])."</b>"));
    }
    
    public function addComment(Event $event) {
        $this->data->create("text", array(
            "comment_id" => $id = $event->getInfo("id"),
            "name"       => $event->getInfo("name"),
            "email"      => $event->getInfo("email"),
            "homepage"   => $event->getInfo("homepage"),
            "text"       => strip_tags($event->getInfo("comment")),
            "status"     => 0
        ));
    }
    
    public function setFrontendVars($data) {
        $this->module("jquery")->addPlugin("json-2.3");
        
        return array_merge($data->attributes(), array(
            "domId" => "comment-".\time().$data->id,
            "comments" => array_reverse($this->data->getAttributes($this->data->all("text", array("conditions" => array("status > 1 AND comment_id = ?", $data->id)))))
        ));
    }
    
    public function preview($data) {
        return $data->attributes(array(
            "*",
            "texts",
            "count" => $this->data->count("text", array("conditions" => array("status > 1 AND comment_id = ?", $data->id)))
        ));
    }
    
    public function defineBackend() {
        parent::defineBackend();
        
        $comments = $this->data->all("text", array("conditions" => array("status < 2"), "order" => "created DESC"));
        foreach($comments as $comment) {
            if($comment->status == 0) {
                $comment->status = 1;
                $comment->save();
            }
        }
        
        $this->framework->startGroup("LazyMainTab", array("title" => "New comments"))
            ->list("Finder", array(
                "data" => $this->data->getAttributes($comments),
                "renderer" => array(
                    "name" => "CommentRenderer",
                    "events" => array(
                        "event" => "editComment"
                    )
                ),
                "groupEvents" => "comments"
            ));
        
        $this->framework->startGroup("LazyMainTab", array("title" => "Trash"))
            ->list("Finder", array(
                "data" => $this->data->getAttributes($this->data->find_all_by_deleted("text", 1)),
                "renderer" => array(
                    "name" => "CommentRenderer"
                )
            ));
            
    }
    
    
    public function defineEditor(Event $event) {
        $settings = $event->getInfo("settings");
        $isNew = $settings["_isNew"];
        
        $this->framework
            ->key("elements")
                ->input("Checkbox", array(
                    "label"   => "email",
                    "infoKey" => "has_email",
                    "events"  => $settings["events"],
                    "value"   => $isNew ? false : $settings["data"]["has_email"]
                ))
                ->input("Checkbox", array(
                    "label"   => "homepage",
                    "infoKey" => "has_homepage",
                    "events"  => $settings["events"],
                    "value"   => $isNew ? false : $settings["data"]["has_homepage"]
                ))
                ->input("Checkbox", array(
                    "label"   => "subject",
                    "infoKey" => "has_subject",
                    "events"  => $settings["events"],
                    "value"   => $isNew ? false : $settings["data"]["has_subject"]
                ));
    }
}


?>