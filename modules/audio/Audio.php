<?php
/**
 * Module Audio
 * 
 * PHP version 5.3
 * 
 * @author  Friedolin Förder <friedolin.foerder@pinion-cms.org>
 * @license MIT License
 * @link    http://www.pinion-cms.org/module/audio
 */

namespace modules\audio;

use \pinion\modules\Module;
use \pinion\modules\FrontendModule;
use \pinion\events\Event;
use \pinion\general\TemplateBuilder;

class Audio extends FrontendModule {
    
    
    public function install() {
        $this->data
            ->createDataStorage("audio", array(
                "file" => "fileupload"
            ));
    }
    
    
    
    public function defineBackend() {
        parent::defineBackend();
        
        $this->framework
            ->text("Headline", array("text" => "This is the backend area of the module \"Audio\""));
            
    }
    
    
    public function add(Event $event) {
        
        
        // the new data
        $data = array();
        
        
        
        // fill the data with information from the event...
        $files = $event->getInfo("files");
        foreach($files as $name => $file) {
            $file = $this->module("fileupload")->data->find_by_directory_and_filename("file", "audio", $name);
            if(is_object($file)) {
                $data["file_id"] = $file->id;
            }
        }
        
        
        // create the data storage
        $audio = $this->data->create($this->name, $data);
        
        
        // give the event and the storage data to the page,
        // so the content can be linked with the page
        $this->module("page")->setModuleContentMapping($event, $audio);
        
        
        // add a success message
        $this->response->addSuccess($this, $this->translate("%s added", "<b>".$this->translate($this->information["title"])."</b>"));
    }
    
    
    public function edit(Event $event) {
        
        // get the id from the event
        $id = $event->getInfo("id");
        
        
        // get the data storage
        $audio = $this->data->find($this->name, $id);
        
        
        
        // update the data storage...
        $files = $event->getInfo("files");
        foreach($files as $name => $file) {
            $file = $this->module("fileupload")->data->find_by_directory_and_filename("file", "audio", $name);
            if(is_object($file)) {
                $audio->file_id = $file->id;
            }
        }
        
        
        // save the data storage
        $audio->save();
        
        
        // dispatch the content event of the page
        // to update the contents on the page
        $this->module("page")->dispatchEvent("content", array("data" => $audio));
        
        
        // add a success message
        $this->response->addSuccess($this, $this->translate("%s edited", "<b>".$this->translate($this->information["title"])."</b>"));
    }
    
    public function defineEditor(Event $event) {
        $settings = $event->getInfo("settings");
        
        $this->framework
            ->key("elements");
                if($this->module("fileupload")->hasPermission("upload file")) {
                    $this->framework
                        ->input("Fileuploader", array(
                            "infoKey" => "audio",
                            "events" => $settings["events"]
                        ));
                } else {
                    $this->framework
                        ->html("SimpleHtml", array("html" => $this->translate("You don't have the permission to upload a file")));
                }
                
    }
    
    protected function _run() {
        $this->response->addJs("audiojs", "module:audio");
    }
    
    public function preview($data) {
        return $this->setFrontendVars($data);
    }
    
    public function setFrontendVars($data) {
        
        $this->run();
        
        return $data->attributes(array(
            "*",
            "filename" => $data->file->filename,
            "src" => $this->module("fileupload")->data->filesUrl()."audio/".$data->file->filename
        ));
    }
        
}


?>