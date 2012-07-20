<?php
/**
 * Module Downloader
 * 
 * PHP version 5.3
 * 
 * @author  Friedolin Förder <friedolin.foerder@pinion-cms.org>
 * @license MIT License
 * @link    http://www.pinion-cms.org/module/downloader
 */

namespace modules\downloader;

use \pinion\modules\Module;
use \pinion\modules\FrontendModule;
use \pinion\events\Event;
use \pinion\general\TemplateBuilder;
use \pinion\files\FileInformation;

class Downloader extends FrontendModule {
    
    public function install() {
        $this->data
            ->createDataStorage("downloader", array(
                "label" => array("type" => "varchar", "length" => 100),
                "file"  => "fileupload"
            ))
            ->createDataStorage("archive", array(
                "count" => array("type" => "int"),
                "file"  => "fileupload"
            ), array(
                "revisions" => false
            ));
    }
    
    
    public function setFrontendVars($data) {
        
        $count = $this->data->find_by_file_id("archive", $data->file_id)->count;
        
        return $data->attributes(array(
            "*",
            "count" => $count
        ));
    }
    
    
    public function addListener() {
        parent::addListener();
        
        $this->addEventListener("download");
        $this->addEventListener("search");
    }
    
    public function search(Event $event) {
        $value = $event->getInfo("value");
        return $this->data->all("downloader", array("conditions" => array("label LIKE ?", '%'.$value.'%')));
    }
    
    
    public function download(Event $event) {
        $id = $event->getInfo("id");
        if(is_null($id)) {
            $file = $event->getInfo("file");
            if(is_null($file)) {
                die();
            } else {
                $fileuploadFile = $this->module("fileupload")->data->find_by_filename("file", $file);
                if(is_null($fileuploadFile)) {
                    die();
                } else {
                    $id = $fileuploadFile->id;
                }
            }
        }
        
        $archive = $this->data->find_by_file_id("archive", $id);
        if(is_object($archive)) {
            $archive->count++;
            $archive->save();

            $file = $this->module("fileupload")->data->find_by_id("file", $id);
            if(is_object($file)) {
                $filename = $file->filename;
                
                header("Content-Type: ".FileInformation::mimeType($filename));
                header("content-disposition: attachment; filename=$filename");
                @readfile($this->module("fileupload")->data->filesPath().$file->directory."/".$filename);
                exit;
            }
        }
    }
    
    
    public function add(Event $event) {
        
        $file_id = $event->getInfo("file");
        
        $downloader = $this->data->create("downloader", array(
            "label" => $event->getInfo("label"),
            "file_id" => $file_id
        ));
        
        $archive = $this->data->find_by_file_id("archive", $file_id);
        if(is_null($archive)) {
            $archive = $this->data->create("archive", array(
                "file_id" => $file_id,
                "count" => 0
            ));
        }
        
        $this->module("page")->setModuleContentMapping($event, $downloader);
        
        // add success message
        $this->response->addSuccess($this, $this->translate("%s added", "<b>".$this->translate($this->information["title"])."</b>"));
    }
    
    public function edit(Event $event) {
        // get the id from the event
        $id = $event->getInfo("id");
        
        // get the data storage
        $downloader = $this->data->find($this->name, $id);
        
        
        if($event->hasInfo("label")) {
            $downloader->label = $event->getInfo("label");
        }
        if($event->hasInfo("file")) {
            $downloader->file_id = $event->getInfo("file");
            
            $archive = $this->data->find_by_file_id("archive", $downloader->file_id);
            if(is_null($archive)) {
                $archive = $this->data->create("archive", array(
                    "file_id" => $event->getInfo("file"),
                    "count" => 0
                ));
            }
        }
        
        // save the data storage
        $downloader->save();
        
        // dispatch the content event of the page
        // to update the contents on the page
        $this->module("page")->dispatchEvent("content", array("data" => $downloader));
        
        
        // add a success message
        $this->response->addSuccess($this, $this->translate("%s edited", "<b>".$this->translate($this->information["title"])."</b>"));
    }
    
    public function defineEditor(Event $event) {
        $settings = $event->getInfo("settings");
        
        $this->framework
            ->key("elements")
                ->input("Textbox", array(
                    "label" => "label",
                    "events" => $settings["events"],
                    "validators" => array(
                        "notEmpty" => true
                    )
                ))
                ->list("DataPager", array(
                    "display" => array(
                        "label" => "file",
                        "selectable" => true,
                        "multiple" => false,
                        "infoKey" => "file",
                        "renderer" => "FileRenderer",
                        "events" => $settings["events"],
                        "validators" => array(
                            "notEmpty" => true
                        )
                    ),
                    "data" => array(
                        "event" => "getFiles",
                        "module" => "fileupload"
                    ),
                    "groupEvents" => true
                ));
    }
    
    public function preview($data) {
        return $data->attributes(array(
            "*",
            "file"
        ));
    }
    
    public function defineBackend() {
        $downloads = $this->data->all("archive", array("order" => "count DESC", "limit" => 25));
        $sum = $this->data->find("archive", array("select" => "sum(count) as sum"));
        $sum = $sum->sum;
        
        if(isset($downloads[0])) {
            $biggestCount = $downloads[0]->count;
            
            $downloadData = $this->data->getAttributes($downloads, array(
                "*",
                "procent" => function($ar) use($sum) {
                    return $sum == 0 ? 0 : sprintf("%1.2f", $ar->count / $sum * 100);
                },
                "bar" => function($ar) use($biggestCount) {
                    return $biggestCount == 0 ? 0 : sprintf("%1.2f", $ar->count / $biggestCount * 100);
                },
                "file"
            ));
        } else {
            $downloadData = array();
        }
        
        
        
        $this->framework
            ->startGroup("LazyMainTab", array("title" => "Download archive"))
                ->list("Finder", array(
                    "data" => $downloadData,
                    "renderer" => array(
                        "name" => "DownloadRenderer"
                    )
                ));
    }
}


?>