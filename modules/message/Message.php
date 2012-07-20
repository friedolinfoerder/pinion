<?php
/**
 * Module Message
 * 
 * PHP version 5.3
 * 
 * @author  Friedolin Förder <friedolin.foerder@pinion-cms.org>
 * @license MIT License
 * @link    http://www.pinion-cms.org/module/message
 */

namespace modules\message;

/**
 * Description of Message
 * 
 * @category   data
 * @package    pinion
 * @subpackage database
 * @author     Friedolin Förder <friedolinfoerder@pinion-cms.de>
 * @license    license placeholder
 * @link       http://www.pinion-cms.de
 */

use \pinion\modules\Module;
use \pinion\modules\Renderer;
use \pinion\events\Event;
use \pinion\modules\ModuleManager;

class Message extends Module {
    
    public function install() {
        $this->data
            ->createDataStorage("message", array(
                "title"    => array("type" => "varchar", "length" => 100, "translatable" => false),
                "text"     => array("type" => "text", "translatable" => false),
                "receiver" => array("type" => "int"),
                "status"   => array("type" => "int")
            ));
    }
    
    public function getResources() {
        return array_merge(parent::getResources(), array(
            "write message to user"
        ));
    }
    
    public function addListener() {
        parent::addListener();
        
        $this->addEventListener("read");
        if($this->hasPermission("write message to user"))  $this->addEventListener("addMessage");
    }
    
    public function read(Event $event) {
        
        $message = $this->data->find("message", $event->getInfo("id"));
        $message->status = 2;
        $message->save();
        
        $this->response->addSuccess($this, $this->translate("The message %s has marked as read", "<b>".$event->getInfo("title")."</b>"));
    }
    
    public function addMessage(Event $event) {
        
        $to = $event->getInfo("to");
        
        $this->data->create("message", array(
            "title"    => $event->getInfo("title"),
            "text"     => $event->getInfo("message"),
            "receiver" => $to,
            "status"   => 0
        ));
        
        $user = $this->module("permissions")->data->find("user", $to);
        $user->hasmessages = true;
        $user->save();
        
        $this->response->addSuccess($this, $this->translate("Your message has been sent"));
    }
    
    public function defineBackend() {
        
        $otherUser = $this->module("permissions")->data->all("user", array("conditions" => array("id != ?", $this->identity->userid)));
        
        $messages = $this->data->all("message", array("conditions" => array("receiver = ?", $this->identity->userid), "order" => "created DESC"));
        foreach($messages as $message) {
            if($message->status == 0) {
                $message->status = 1;
                $message->save();
            }
        }
        $messages = $this->data->getAttributes($messages, array(
            "title",
            "user"  => function($ar) {
                if($ar) {
                    return $ar->username;
                }
            },
            "status",
            "text",
            "created" => function($value) {
                return date("d.m.Y – H:i", $value);
            }
        ));
            
        
        if($this->hasPermission("write message to user")) {
            $this->framework
                ->startGroup("LazyMainTab", array("title" => "Write message", "validate" => "all", "groupEvents" => true))
                    ->list("Selector", array(
                        "label" => "to",
                        "validators" => array(
                            "notEmpty" => true
                        ),
                        "data" => $this->data->getAttributes($otherUser, array("username")),
                        "events" => array("event" => "addMessage")
                    ))
                    ->input("Textbox", array(
                        "label" => "title",
                        "validators" => array(
                            "notEmpty" => true
                        ),
                        "events" => array("event" => "addMessage")
                    ))
                    ->input("Textarea", array(
                        "label" => "message",
                        "validators" => array(
                            "notEmpty" => true
                        ),
                        "events" => array("event" => "addMessage")
                    ));
        }
        
        
        $this->framework
            ->startGroup("LazyMainTab", array("title" => "User messages"))
                ->list("Finder", array(
                    "renderer" => "UserMessageRenderer",
                    "data" => $messages
                ));
        
        $this->framework
            ->startGroup("LazyMainTab", array("title" => "System messages"))
                ->list("DataPager", array(
                    "display" => array(
                        "renderer" => "SystemMessageRenderer"
                    ),
                    "identifier" => "systemMessagesList",
                    "data" => array()
                ));
    }
    
}

?>
