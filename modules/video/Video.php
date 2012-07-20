<?php
/**
 * Module Video
 * 
 * PHP version 5.3
 * 
 * @author  Friedolin Förder <friedolin.foerder@pinion-cms.org>
 * @license MIT License
 * @link    http://www.pinion-cms.org/module/video
 */

namespace modules\video;

use \pinion\modules\Module;
use \pinion\modules\FrontendModule;
use \pinion\events\Event;
use \pinion\general\TemplateBuilder;

class Video extends FrontendModule {
    
    
    public function install() {
        $this->data
            ->createDataStorage("video", array(
                "file" => "fileupload"
            ))
            ->createDataStorage("videofile", array(
                "file" => "fileupload",
                "video"
            ));
    }
    
    
    public function addListener() {
        parent::addListener();
        
        $this->addEventListener("search");
    }
    
    public function search(Event $event) {
        $value = $event->getInfo("value");
        
        $all = $this->module("fileupload")->data->all("file", array("conditions" => array("directory = ? AND filename LIKE ?", "video", '%'.$value.'%')));
        if(empty($all)) return array();
        $ids = array();
        foreach($all as $l) {
            $ids[] = $l->id;
        }
        
        $all = $this->data->all("videofile", array("conditions" => array("file_id IN (?)", $ids)));
        if(empty($all)) return array();
        $ids = array();
        foreach($all as $l) {
            $ids[] = $l->video_id;
        }
        
        return $this->data->all("video", array("conditions" => array("id IN (?)", $ids)));
    }
    
    
    public function add(Event $event) {
        
        $files = $event->getInfo("files");
        
        // create the data storage
        $video = $this->data->create($this->name, array());
        
        foreach($files as $name => $file) {
            $file = $this->module("fileupload")->data->find_by_directory_and_filename("file", "video", $name);
            if(is_object($file)) {
                $videofile = $this->data->create("videofile", array(
                    "file_id" => $file->id,
                    "video_id" => $video->id
                ));
            }
        }
        
        // give the event and the storage data to the page,
        // so the content can be linked with the page
        $this->module("page")->setModuleContentMapping($event, $video);
        
        
        // add a success message
        $this->response->addSuccess($this, $this->translate("%s added", "<b>".$this->translate($this->information["title"])."</b>"));
    }
    
    
    public function edit(Event $event) {
        
        // get the id from the event
        $id = $event->getInfo("id");
        
        
        // get the data storage
        $video = $this->data->find($this->name, $id);
        
        
        
        // update the data storage...
        
        
        
        // save the data storage
        $video->save();
        
        
        // dispatch the content event of the page
        // to update the contents on the page
        $this->module("page")->dispatchEvent("content", array("data" => $video));
        
        
        // add a success message
        $this->response->addSuccess($this, $this->translate("%s edited", "<b>".$this->translate($this->information["title"])."</b>"));
    }
    
    protected function _run() {
        $this->response->addJs("videojs", "module:video");
    }
    
    public function setFrontendVars($data) {
        
        $this->run();
        
        return $data->attributes(array(
            "*",
            "js" => "_V_.options.flash.swf = '".MODULES_URL."/video/templates/js/videojs/video-js.swf';",
            "path" => $this->module("fileupload")->getFilesUrl()."video/",
            "file",
            "videofiles" => array(
                "file" => array(
                    "*",
                    "type" => function($ar) {
                        $parts = explode(".", $ar->filename);
                        return end($parts);
                    }
                )
            )
        ));
    }
    
    public function preview($data) {
        return $this->setFrontendVars($data);
    }
    
    public function defineEditor(Event $event) {
        $settings = $event->getInfo("settings");
        
        $this->framework
            ->key("elements");
                if($this->module("fileupload")->hasPermission("upload file")) {
                    $this->framework
                        ->input("Fileuploader", array(
                            "infoKey" => "files",
                            "events" => $settings["events"]
                        ));
                } else {
                    $this->framework
                        ->html("SimpleHtml", array("html" => $this->translate("You don't have the permission to upload a file")));
                }
                
    }
}


?>