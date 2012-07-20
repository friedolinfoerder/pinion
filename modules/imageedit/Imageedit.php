<?php
/**
 * Module Imageedit
 * 
 * PHP version 5.3
 * 
 * @author  Friedolin Förder <friedolin.foerder@pinion-cms.org>
 * @license MIT License
 * @link    http://www.pinion-cms.org/module/imageedit
 */

namespace modules\imageedit;

use \pinion\modules\Module;
use \pinion\modules\FrontendModule;
use \pinion\events\Event;
use \pinion\general\TemplateBuilder;

class Imageedit extends Module {
    
    
    public function defineBackend() {
        parent::defineBackend();
        
        $this->module("colorbox")->run();
        
        $this->framework
            ->startGroup("LazyMainTab", array("title" => "edit"))
                ->list("DataPager", array(
                    "display" => array(
                        "name" => "ImageGrid",
                        "type" => "image",
                        "dirty" => "never",
                        "eval" => "this.on('click', function(data) {
                            pinion.modules.imageedit.edit(data);
                        });"
                    ),
                    "dataPerSite" => 30,
                    "data" => array(
                        "event" => "getImages",
                        "module" => "image",
                        "info" => array()
                    )
                ));
            
    }
    
    public function getResources() {
        return array_merge(parent::getResources(), array(
            "crop image"
        ));
    }
    
    public function addListener() {
        parent::addListener();
        
        if($this->identity) {
            if($this->hasPermission("crop image")) $this->addEventListener("crop");
        }
    }
    
    public function crop(Event $event) {
        $id = $event->getInfo("id");
        $type = $event->getInfo("type");
        $x = $event->getInfo("x");
        $y = $event->getInfo("y");
        $w = $event->getInfo("w");
        $h = $event->getInfo("h");
        
        $image = $this->module("image");
        
        $image
            ->load($id)
            ->crop($x, $y, $w, $h);
        
        if($type == "create") {
            $info = $image->getLoadedImageInfo();
            $file = explode(".", $info->getFilename());
            $ext = array_pop($file);
            $file = join(".", $file);

            $filename = $file."_".time().".$ext";

            $image
                ->getLoadedImage()
                ->saveToFile($this->module("fileupload")->data->filesPath()."images/$filename");

            $fileData = $this->module("fileupload")->data->create("file", array(
                "filename" => $filename,
                "directory" => "images"
            ));

            $image->addImage($fileData->id);
        } elseif($type == "overwrite") {
            $info = $image->getLoadedImageInfo();
            $filename = $info->getFilename();
            
            $image
                ->getLoadedImage()
                ->saveToFile($this->module("fileupload")->data->filesPath()."images/$filename");
        }
        
    }
        
}


?>