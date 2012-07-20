<?php
/**
 * Module Image
 * 
 * PHP version 5.3
 * 
 * @author  Friedolin Förder <friedolin.foerder@pinion-cms.org>
 * @license MIT License
 * @link    http://www.pinion-cms.org/module/image
 */

namespace modules\image;

use \pinion\modules\Module;
use \pinion\modules\FrontendModule;
use \pinion\data\models\Image_preset;
use \pinion\data\models\Image_file;
use \pinion\events\Event;


class Image extends FrontendModule {
    
    protected $loadedImage;
    protected $dbImage;
    
    protected $imagePath;
    protected $imageUrl;
    /**
     *
     * @var \SplFileInfo $loadedImageFileInfo
     */
    protected $loadedImageFileInfo;
    
    public function init() {
        require_once 'WideImage/lib/WideImage.php';
    }
    
    public function install() {
        
        $this->data->createDataStorage("file", array(
            "file"     => "fileupload",
            "title"    => array("type" => "varchar", "length" => 500, "isNull" => true),
            "alt"      => array("type" => "varchar", "length" => 500, "isNull" => true)
        ));
        
        $this->data->createDataStorage("preset", array(
            "name" => array("type" => "varchar", "length" => 100, "translatable" => false)
        ));
        
        $this->data->createDataStorage("function", array(
            "name"     => array("type" => "varchar", "length" => 50, "translatable" => false),
            "value1"   => array("type" => "varchar", "length" => 50, "isNull" => true, "translatable" => false),
            "value2"   => array("type" => "varchar", "length" => 50, "isNull" => true, "translatable" => false),
            "value3"   => array("type" => "varchar", "length" => 50, "isNull" => true, "translatable" => false),
            "value4"   => array("type" => "varchar", "length" => 50, "isNull" => true, "translatable" => false),
            "value5"   => array("type" => "varchar", "length" => 50, "isNull" => true, "translatable" => false),
            "value6"   => array("type" => "varchar", "length" => 50, "isNull" => true, "translatable" => false),
            "position" => array("type" => "int"),
            "preset"
        ));
        
        $this->data->createDataStorage("image", array(
            "preset",
            "height" => array("type" => "int", "isNull" => true),
            "width" => array("type" => "int", "isNull" => true),
            "use1" => array("type" => "int", "isNull" => true),
            "preset1" => array("type" => "int", "isNull" => true),
            "height1" => array("type" => "int", "isNull" => true),
            "width1" => array("type" => "int", "isNull" => true),
            "use2" => array("type" => "int", "isNull" => true),
            "preset2" => array("type" => "int", "isNull" => true),
            "height2" => array("type" => "int", "isNull" => true),
            "width2" => array("type" => "int", "isNull" => true),
            "use3" => array("type" => "int", "isNull" => true),
            "preset3" => array("type" => "int", "isNull" => true),
            "height3" => array("type" => "int", "isNull" => true),
            "width3" => array("type" => "int", "isNull" => true)
        ));
        
        $this->data->createDataStorage("imagefile", array(
            "position" => array("type" => "int"),
            "file",
            "image"
        ));
        
        // CREATE EXAMPLE PRESET
        $preset = $this->data->create("preset", array(
            "name" => "example"
        ));
        
        $this->data->create("function", array(
            "name"      => "crop",
            "value1"    => "25%",
            "value2"    => "25%",
            "value3"    => "50%",
            "value4"    => "50%",
            "position"  => 0,
            "preset_id" => $preset->id
        ));
        
        $this->data->create("function", array(
            "name"      => "resize",
            "value1"    => "200",
            "value2"    => "200",
            "position"  => 1,
            "preset_id" => $preset->id
        ));
        
        $this->data->create("function", array(
            "name"      => "asGrayscale",
            "position"  => 2,
            "preset_id" => $preset->id
        ));
        
        // CREATE GRAYSCALE PRESET
        $preset = $this->data->create("preset", array(
            "name" => "grayscale"
        ));
        
        $this->data->create("function", array(
            "name"      => "asGrayscale",
            "position"  => 0,
            "preset_id" => $preset->id
        ));
    }
    
    public function addListener() {
        parent::addListener();
        
        if($this->identity) {
            $this->addEventListener("addPreset");
            $this->addEventListener("getImages");
            $this->addEventListener("getPresets");
            $this->addEventListener("editPresets");
            if($this->hasPermission("has existing content")) $this->addEventListener("getContents");
            $this->addEventListener("changeFiles");
            $this->addEventListener("image");
        }
        $this->addEventListener("search");
    }
    
    public function search(Event $event) {
        $value = $event->getInfo("value");
        
        $all = $this->module("fileupload")->data->all("file", array("conditions" => array("directory = ? AND filename LIKE ?", "image", '%'.$value.'%')));
        if(empty($all)) return array();
        $ids = array();
        foreach($all as $l) {
            $ids[] = $l->id;
        }
        
        $all = $this->data->all("file", array("conditions" => array("file_id IN (?)", $ids)));
        if(empty($all)) return array();
        $ids = array();
        foreach($all as $l) {
            $ids[] += $l->id;
        }
        
        $all = $this->data->all("imagefile", array("conditions" => array("file_id IN (?)", $ids)));
        if(empty($all)) return array();
        $ids = array();
        foreach($all as $l) {
            $ids[] = $l->image_id;
        }
        
        return $this->data->all("image", array("conditions" => array("id IN (?)", $ids)));
    }
    
    public function image(Event $event) {
        $id = $event->getInfo("id");
        
        $imageFile = $this->data->find("file", $id);
        
        
        $path = $this->getImage($imageFile, array(), null, null, true);
        if(is_file($path)) {
            $ext = explode(".", $path);
            $ext = end($ext);
            header("Content-Type: image/$ext");
            readfile($path);
            exit;
        }
    }
    
    public function getResources() {
        return array_merge(parent::getResources(), array(
            "edit image",
            "add preset",
            "edit preset",
            "delete preset"
        ));
    }
    
    public function changeFiles(Event $event) {
        $files = $event->getInfo("files");
        if($files) {
            
            foreach($files as $file) {
                $id = $file["id"];
                if(isset($file["deleted"]) && $file["deleted"] == true) {
                    $this->module("fileupload")->dispatchEvent("delete", array(
                        "dir" => "images",
                        "file" => $file["file"]
                    ));
                } elseif($this->hasPermission("edit image")) {
                    
                    // TODO: REFACTORING: THE MODULE FILEUPLOAD RENAMES THE FILES
                    
//                    if(isset($file["filename"])) {
//                        $path = $this->module("fileupload")->getFilesPath()."images/";
//                        if(file_exists($path.$file["filename"])) {
//                            // you can't rename the file, because there is already a file with this name
//                            $this->response->addError($this, $this->translate("A file with the filename %s already exists. The file %s was not renamed.", "<i>".$file["filename"]."</i>", "<i>".$file["file"]."<i>"));
//                            unset($file["filename"]);
//                        } else {
//                            // rename file
//                            rename($path.$file["file"], $path.$file["filename"]);
//                            $this->response->addSuccess($this, $this->translate("The file %s was renamed to %s.", "<i>".$file["file"]."</i>", "<i>".$file["filename"]."</i>"));
//                        }
//                    }
//                    $this->data->find("file", $id)->update_attributes($file);
                }
            }
        } else {
            $this->response->addError($this, $this->translate(true, "no %s given", "files"));
        }
    }
    
    public function editPresets(Event $event) {
        
        // find preset
        $id = $event->getInfo("id");
        $name = $event->getInfo("newName");
        
        $created = false;
        if(is_null($id)) {
            if($this->hasPermission("add preset")) {
                $preset = $this->data->create("preset", array(
                    "name" => $name
                ));
                $id = $preset->id;
                $this->response->addSuccess($this, $this->translate("The preset %s was created", "<b>".$preset->name."</b>"));
                $created = true;
            } else {
                return;
            }
        } else {
            $preset = $this->data->find("preset", $id);
            
            $deleted = $event->getInfo("deleted");
            if($deleted === true) {
                if($this->hasPermission("delete preset")) {
                    $preset->delete();
                    return $this->response->addSuccess($this, $this->translate("The preset %s was deleted", "<b>".$preset->name."</b>"));
                } else {
                    return;
                }
            }
            
            if($this->hasPermission("edit preset")) {
                if($name) {
                    $preset->name = $name;
                }
                $preset->forceSave();
            } else {
                return;
            }
        }
        
        
        
        $functions = $event->getInfo("functions");
        $values = $event->getInfo("values");
        
        
        
        $idMapping = array();
        
        if($functions) {
            $count = 0;
            foreach($functions as $function) {
                if(isset($function["id"])) {
                    $func = $this->data->find("function", $function["id"]);
                    if(isset($function["deleted"])) {
                        $func->delete();
                    } else {
                        $func->position = $count++;
                        $func->save();
                    }
                    
                } elseif(isset($function["newId"])) {
                    $func = $this->data->create("function", array(
                        "name" => $function["name"],
                        "position" => $count++,
                        "preset_id" => $id
                    ));
                    $idMapping[$function["newId"]] = $func->id;
                }
            }
        }
        if($values) {
            foreach($values as $infos) {
                if(isset($infos["id"])) {
                    $funcId = $infos["id"];
                } elseif(isset($infos["newId"]) && isset($idMapping[$infos["newId"]])) {
                    $funcId = $idMapping[$infos["newId"]];
                } else {
                    $funcId = null;
                }
                if(!is_null($funcId)) {
                    $func = $this->data->find("function", $funcId);
                    foreach($infos["values"] as $index => $value) {
                        $propertyName = "value".($index+1);
                        $func->{$propertyName} = $value;
                    }
                    $func->save();
                }
                
            }
        }
        
        if(!$created) {
            $this->response->addSuccess($this, $this->translate("The preset %s was updated", "<b>".$preset->name."</b>"));
        }
    }
    
    public function getImages(Event $event) {
        $options = array("order" => "created desc");
        $start = $event->getInfo("start");
        $end = $event->getInfo("end");
        
        $options["offset"] = $start;
        $options["limit"] = $end - $start;
        
        $images = $this->data->all("file", $options);
        $urls = array();
        
        foreach($images as $image) {
            $urls[] = array(
                "id"  => $image->id,
                "src" => $this->getThumb($image, "Big")
            );
        }
        
        $this->response->setInfo("data", $urls);
        
        if($start == 0) {
            $this->response->setInfo("dataLength", $this->data->count("file"));
        }
    }
    
    public function getPresets(Event $event) {
        
        $presets = $this->data->getAttributes("preset", array("id", "name"));
        
        $this->response->setInfo("data", $presets);
    }
    
    public function resizeAndCrop($width, $height) {
        $this->loadedImage = $this->loadedImage
            ->resize($width, $height, "outside", "any")
            ->crop("center", "center", $width, $height);
        
        return $this;
    }
    
    public function __call($name, $arguments) {
        $this->loadedImage = call_user_func_array(array($this->loadedImage, $name), $arguments);
        
        return $this;
    }
    
    public function getLoadedImageInfo() {
        return $this->loadedImageFileInfo;
    }
    
    public function getLoadedImage() {
        return $this->loadedImage;
    }
    
    /**
     *
     * @param Image_file|string $image
     * @return Image 
     */
    public function load($image) {
        if(is_numeric($image)) {
            $image = $this->data->find("file", $image);
            $file = $image->file;
        } elseif(is_string($image)) {
            $file = $this->module("fileupload")->data->find_by_filename("file", $image);
        } else {
            $file = $image->file;
        }
        $filename = $file->filename;
        
        $this->dbImage = $image;
        $this->imagePath = $this->module("fileupload")->data->filesPath()."images/".$filename;
        $this->imageUrl = $this->module("fileupload")->data->filesUrl()."images/".rawurlencode($filename);
        
        $this->loadedImageFileInfo = new \SplFileInfo($this->imagePath);
        $this->loadedImage = call_user_func(array("\WideImage", "load"), $this->imagePath);
        
        return $this;
    }
    
    public function save($identifier) {
        $args = func_get_args();
        $filename = $this->loadedImageFileInfo->getFilename();
        
        $ext = explode(".", $filename);
        $ext = end($ext);
        
        $dir = $this->module("fileupload")->data->filesPath()."images/edited/{$this->dbImage->id}";
        
        if(!is_dir($dir)) {
            mkdir($dir);
        }
        
        $this->imagePath = "$dir/$identifier.$ext";
        $this->imageUrl = $this->module("fileupload")->data->filesUrl()."images/edited/{$this->dbImage->id}/$identifier.$ext";
        
        $args[0] = "$dir/$identifier.$ext";
        call_user_func_array(array($this->loadedImage, "saveToFile"), $args);
        
        return $this;
    }
    
    public function getThumb(Image_file $image, $size = "") {
        $ext = explode(".", $image->file->filename);
        $ext = end($ext);
        return $this->module("fileupload")->data->filesUrl()."images/edited/{$image->id}/thumb$size.$ext";
    }
    
    /**
     *
     * @param Image_file $file
     * @param array      $preset
     * @param int        $width optional
     * @param int        $height optional
     * 
     * @return string The url of the image 
     */
    public function getImage($file, array $presets = array(), $width = null, $height = null, $getPath = false) {
        if(is_numeric($file)) {
            $file = $this->data->find("file", $file);
        } elseif(is_string($file)) {
            $file = $this->module("fileupload")->data->find_by_filename("file", $file)->files[0];
        }
        $realPresets = array();
        
        foreach($presets as $pre) {
            if(is_numeric($pre)) {
                $pre = $this->data->find("preset", $pre);
            } elseif(is_string($pre)) {
                $pre = $this->data->find_by_name("preset", $pre);
            }
            
            if($pre instanceof Image_preset) {
                $realPresets[] = $pre;
            }
        }
        
        return call_user_func(array($this, "_getImage"), $file, $realPresets, $width, $height, $getPath);
    }
    
    /**
     *
     * @param Image_file $image
     * @param array $preset
     * 
     * @return string The url of the image 
     */
    protected function _getImage(Image_file $image, array $presets, $width = null, $height = null, $getPath = false) {
        $filename = $image->file->filename;
        $hasSize = !(empty($width) || empty($height));
        if(empty($presets)) {
            if(!$hasSize) {
                if($getPath) {
                    return $this->module("fileupload")->data->filesPath()."images/$filename";
                } else {
                    return $this->module("fileupload")->data->filesUrl()."images/$filename";
                }
            }
        }
        
        
        $ext = explode(".", $filename);
        $ext = end($ext);
        
        $editedFilename = array();
        foreach($presets as $preset) {
            $editedFilename[] = "{$preset->id}_{$preset->revision}";
        }
        
        if($hasSize) {
            $editedFilename[] = "{$width}x{$height}";
        }
        $editedFilename = join("__", $editedFilename);
        
        
        $filePath = "images/edited/{$image->id}/$editedFilename.$ext";
        $file = $this->module("fileupload")->data->filesPath().$filePath;
        
        if(!is_file($file)) {
            $this->load($image);
            
            foreach($presets as $preset) {
                $this->processPreset($preset);
            }
            if($hasSize) {
                $this->resizeAndCrop($width, $height);
            }
            
            $this->save($editedFilename);
        }
        
        if($getPath) {
            return $file;
        } else {
            return $this->module("fileupload")->data->filesUrl().$filePath;
        }
    }
    
    public function processPreset(Image_preset $preset) {
        $functions = $preset->functions;
            
        foreach($functions as $function) {
            $args = array();
            for($i = 1; $i <= 6; $i++) {
                if(!is_null($function->{"value".$i})) {
                    $args[] = $function->{"value".$i};
                }
            }
            $this->loadedImage = call_user_func_array(array($this->loadedImage, $function->name), $args);
        }
        return $this->imagePath;
    }
    
    public function getPath() {
        return $this->imagePath;
    }
    
    public function getUrl() {
        return $this->imageUrl;
    }
    
    public function preview($data) {
        $image = $data->attributes(array(
            "preset" => array(
                "name",
                "functions" => array(
                    "name"
                )
            ),
            "imagefiles" => array(
                "file" => function($ar) {
                    return $ar;
                }
            )
        ));
        $imageData = array(
            "id"        => $image["id"],
            "preset"    => $image["preset"],
            "images"    => array()
        );
        foreach($image["imagefiles"] as $imagefile) {
            $imageData["images"][] = array(
                "src"       => $this->getThumb($imagefile["file"]),
                "srcPreset" => $this->getImage($imagefile["file"], array($data->preset))
            );
        }
        return $imageData;
    }
    
    public function defineBackend() {
        parent::defineBackend();
        
        $presetData = array();
        $presets = $this->data->all("preset");
        
        foreach($presets as $preset) {
            $functions = $preset->functions;
            $functions = $this->data->getAttributes($functions, array("name", "value1", "value2", "value3", "value4", "value5", "value6"));
            foreach($functions as &$function) {
                $function["values"] = array($function["value1"], $function["value2"], $function["value3"], $function["value4"], $function["value5"], $function["value6"]);
                unset($function["value1"], $function["value2"], $function["value3"], $function["value4"], $function["value5"], $function["value6"]);
            }
            
            $presetData[] = array(
                "id" => $preset->id,
                "name" => $preset->name,
                "functions" => $functions,
                "created" => $preset->created,
                "updated" => $preset->updated,
                "revision" => $preset->revision,
                "user" => $preset->user
            );
            
        }
        
        if($this->module("fileupload")->hasPermission("upload file")) {
            $this->framework
                ->startGroup("LazyMainTab", array("title" => "Upload"))
                    ->input("Fileuploader", array(
                        "files" => "images"
                    ));
        }
        
        
        $this->framework
            ->startGroup("LazyMainTab", array("title" => "Images"))
                ->startGroup("LazyTabGroup")
                    ->startGroup("TitledSection", array("title" => "list"))
                        ->list("DataPager", array(
                            "data" => $this->data->getAttributes("file", array(
                                "*",
                                "filename" => function($ar) {
                                    return $ar->file->filename;
                                },
                                "user" => function($value) {
                                    if($value) {
                                        return $value->username;
                                    }
                                }
                            )),
                            "display" => array(
                                "renderer" => array(
                                    "name" => "ImageRenderer",
                                    "events" => array(
                                        "event" => "changeFiles"
                                    )
                                ),
                                "groupEvents" => "files",
                                "selectable" => false
                            )
                        ))
                        ->end()
                    ->startGroup("TitledSection", array("title" => "grid"))
                        ->list("DataPager", array(
                            "display" => array(
                                "name" => "ImageGrid",
                                "type" => "image"
                            ),
                            "dataPerSite" => 30,
                            "data" => array(
                                "event" => "getImages",
                                "module" => $this->name,
                                "info" => array()
                            )
                        ));
        
        $this->framework
            ->startGroup("LazyMainTab", array("title" => "Presets"))
                ->list("Finder", array(
                    "renderer" => array(
                        "name" => "PresetRenderer",
                        "events" => array(
                            "module" => "image",
                            "event" => "editPresets"
                        )
                    ),
                    "data" => $presetData,
                    "selectable" => false
                ))
                ->input("Button", array(
                    "identifier" => "addPresetButton",
                    "label" => "add preset"
                ));
    }
    
    public function addImage($fileId) {
        $image = $this->data->create("file", array(
            "file_id" => $fileId
        ));
        
        $normalThumb = $this
            ->load($image)
            ->resize(250, 75)
            ->save("thumb")
            ->getUrl();
        
        $bigThumb = $this
            ->load($image)
            ->resize(1000, 300)
            ->save("thumbBig")
            ->getUrl();
        
        return array(
            "thumb" => $normalThumb,
            "thumbBig" => $bigThumb,
            "id" => $image->id
        );
    }
    
    public function add(Event $event) {
        
        $images = $event->getInfo("images");
        
        $imageData = $this->data->create("image", array(
            "preset_id" => $event->getInfo("preset"),
            "width" => $event->getInfo("width"),
            "height" => $event->getInfo("height"),
            "preset1" => $event->getInfo("preset1"),
            "width1" => $event->getInfo("width1"),
            "height1" => $event->getInfo("height1"),
            "preset2" => $event->getInfo("preset2"),
            "width2" => $event->getInfo("width2"),
            "height2" => $event->getInfo("height2"),
            "preset3" => $event->getInfo("preset3"),
            "width3" => $event->getInfo("width3"),
            "height3" => $event->getInfo("height3"),
        ));
        
        if($images) {
            $position = 0;
            foreach($images as $image) {
                $this->data->create("imagefile", array(
                    "position" => $position++,
                    "file_id"  => $image["id"],
                    "image_id" => $imageData->id
                ));
            }
        } else {
            $this->response->addWarning($this, $this->translate(true, "no %s added", "images"));
        }
        
        $this->module("page")->setModuleContentMapping($event, $imageData);
        
        
        // add success message
        $this->response->addSuccess($this, $this->translate("%s added", "<b>".$this->translate($this->information["title"])."</b>"));
    }
    
    
    public function edit(Event $event) {
        $id = $event->getInfo("id");
        $images = $event->getInfo("images");
        
        $imageData = $this->data->find("image", $id);
        $imageFiles = $imageData->imagefiles;
        
        if($images) {
            foreach($imageFiles as $imageFile) {
                $imageFile->delete();
            }
            
            $position = 0;
            foreach($images as $image) {
                $this->data->create("imagefile", array(
                    "position" => $position++,
                    "file_id"  => $image["id"],
                    "image_id" => $id
                ));
            }
        }
        
        // MASTER
        if($event->hasInfo("preset")) {
            $preset = $event->getInfo("preset");
            $imageData->preset_id = (trim($preset) == "") ? null : $preset;
        }
        if($event->hasInfo("width")) {
            $width = $event->getInfo("width");
            $imageData->width = (trim($width) == "") ? null : $width;
        }
        if($event->hasInfo("height")) {
            $height = $event->getInfo("height");
            $imageData->height = (trim($height) == "") ? null : $height;
        }
        
        // VERSION 1
        if($event->hasInfo("use1")) {
            $imageData->use1 = $event->getInfo("use1");
        }
        if($event->hasInfo("preset1")) {
            $preset = $event->getInfo("preset1");
            $imageData->preset1 = (trim($preset) == "") ? null : $preset;
        }
        if($event->hasInfo("width1")) {
            $width = $event->getInfo("width1");
            $imageData->width1 = (trim($width) == "") ? null : $width;
        }
        if($event->hasInfo("height1")) {
            $height = $event->getInfo("height1");
            $imageData->height1 = (trim($height) == "") ? null : $height;
        }
        
        // VERSION 2
        if($event->hasInfo("use2")) {
            $imageData->use2 = $event->getInfo("use2");
        }
        if($event->hasInfo("preset2")) {
            $preset = $event->getInfo("preset2");
            $imageData->preset2 = (trim($preset) == "") ? null : $preset;
        }
        if($event->hasInfo("width2")) {
            $width = $event->getInfo("width2");
            $imageData->width2 = (trim($width) == "") ? null : $width;
        }
        if($event->hasInfo("height2")) {
            $height = $event->getInfo("height2");
            $imageData->height2 = (trim($height) == "") ? null : $height;
        }
        
        // VERSION 3
        if($event->hasInfo("use3")) {
            $imageData->use3 = $event->getInfo("use3");
        }
        if($event->hasInfo("preset3")) {
            $preset = $event->getInfo("preset3");
            $imageData->preset3 = (trim($preset) == "") ? null : $preset;
        }
        if($event->hasInfo("width3")) {
            $width = $event->getInfo("width3");
            $imageData->width3 = (trim($width) == "") ? null : $width;
        }
        if($event->hasInfo("height3")) {
            $height = $event->getInfo("height3");
            $imageData->height3 = (trim($height) == "") ? null : $height;
        }
        
        $imageData->save();
        
        // clear cache of table
        $imageData->table()->clear_cache();
        $imageData->reload();
        
        // dispatch content event to update the contents on the page
        $this->module("page")->dispatchEvent("content", array("data" => $imageData));
        
        
        // add success message
        $this->response->addSuccess($this, $this->translate("%s edited", "<b>".$this->translate($this->information["title"])."</b>"));
    }
    
    
    
    public function setFrontendVars($data) {
        $preset = $data->preset;
        $width = $data->width;
        $height = $data->height;
        
        $uses = array(
            "1" => array(
                "use" => $data->use1,
                "preset" => $data->preset1,
                "width" => $data->width1,
                "height" => $data->height1
            ),
            "2" => array(
                "use" => $data->use2,
                "preset" => $data->preset2,
                "width" => $data->width2,
                "height" => $data->height2
            ),
            "3" => array(
                "use" => $data->use3,
                "preset" => $data->preset3,
                "width" => $data->width3,
                "height" => $data->height3
            )
        );
        
        $self = $this;
        
        $images = $this->data->getAttributes($data->imagefiles, array(
            "file" => function($file) use($self, $preset, $width, $height, $uses) {
                $src = $self->getImage($file, array($preset), $width, $height);
                $title = $file->title ?: "";
                $alt = $file->alt ?: "";
                
                $versions = array();
                foreach($uses as $index => $use) {
                    if($use["use"]) {
                        $useSrc = $self->getImage($file, array($use["preset"]), $use["width"], $use["height"]);
                        $versions[$index] = array(
                            "src" => $useSrc,
                            "tag" => "<img src='$useSrc' title='$title' alt='$alt' />"
                        );
                    }
                }
                
                return array(
                    "src"      => $src,
                    "versions" => $versions,
                    "title"    => $title,
                    "alt"      => $alt,
                    "tag"      => "<img src='$src' title='$title' alt='$alt' />",
                    "id"       => $file->id
                );
            }
        ));
        $output = array("images" => array(), "id" => $data->id);
        foreach($images as $data) {
            $output["images"][] = $data["file"];
        }
        $output["preset"] = $preset ? $preset->id : null;
        
        return $output;
    }
    
    public function defineEditor(Event $event) {
        $settings = $event->getInfo("settings");
        $data = $settings["data"];
        $isNew = $settings["_isNew"];
        $events = $settings["events"];
        
        $this->framework
            ->key("elements")
                ->startGroup("LazyTabGroup", array("groupEvents" => true))
                    ->startGroup("TitledSection", array("title" => "master", "groupEvents" => true))
                        ->list("Selector", array(
                            "label" => "preset",
                            "value" => $isNew ? null : $data["preset_id"],
                            "events" => $events,
                            "data" => array(
                                "event" => "getPresets"
                            )
                        ))
                        ->input("Textbox", array(
                            "label" => "width",
                            "value" => $isNew ? null : $data["width"],
                            "events" => $events
                        ))
                        ->input("Textbox", array(
                            "label" => "height",
                            "value" => $isNew ? null : $data["height"],
                            "events" => $events
                        ))
                        ->end()
                    ->startGroup("TitledSection", array("title" => "versions", "groupEvents" => true))
                        ->startGroup("LazyTabGroup", array("groupEvents" => true))
                            ->startGroup("TitledSection", array("title" => "1", "groupEvents" => true))
                                ->input("Checkbox", array(
                                    "label" => "use",
                                    "infoKey" => "use1",
                                    "value" => $isNew ? false : $data["use1"],
                                    "events" => $events
                                ))
                                ->list("Selector", array(
                                    "label" => "preset",
                                    "infoKey" => "preset1",
                                    "events" => $events,
                                    "value" => $isNew ? null : $data["preset1"],
                                    "data" => array(
                                        "event" => "getPresets"
                                    )
                                ))
                                ->input("Textbox", array(
                                    "label" => "width",
                                    "infoKey" => "width1",
                                    "value" => $isNew ? null : $data["width1"],
                                    "events" => $events
                                ))
                                ->input("Textbox", array(
                                    "label" => "height",
                                    "infoKey" => "height1",
                                    "value" => $isNew ? null : $data["height1"],
                                    "events" => $events
                                ))
                                ->end()
                            ->startGroup("TitledSection", array("title" => "2", "groupEvents" => true))
                                ->input("Checkbox", array(
                                    "label" => "use",
                                    "infoKey" => "use2",
                                    "value" => $isNew ? false : $data["use2"],
                                    "events" => $events
                                ))
                                ->list("Selector", array(
                                    "label" => "preset",
                                    "infoKey" => "preset2",
                                    "events" => $events,
                                    "value" => $isNew ? null : $data["preset2"],
                                    "data" => array(
                                        "event" => "getPresets"
                                    )
                                ))
                                ->input("Textbox", array(
                                    "label" => "width",
                                    "infoKey" => "width2",
                                    "value" => $isNew ? null : $data["width2"],
                                    "events" => $events
                                ))
                                ->input("Textbox", array(
                                    "label" => "height",
                                    "infoKey" => "height2",
                                    "value" => $isNew ? null : $data["height2"],
                                    "events" => $events
                                ))
                                ->end()
                            ->startGroup("TitledSection", array("title" => "3", "groupEvents" => true))
                                ->input("Checkbox", array(
                                    "label" => "use",
                                    "infoKey" => "use3",
                                    "value" => $isNew ? false : $data["use3"],
                                    "events" => $events
                                ))
                                ->list("Selector", array(
                                    "label" => "preset",
                                    "infoKey" => "preset3",
                                    "events" => $events,
                                    "value" => $isNew ? null : $data["preset3"],
                                    "data" => array(
                                        "event" => "getPresets"
                                    )
                                ))
                                ->input("Textbox", array(
                                    "label" => "width",
                                    "infoKey" => "width3",
                                    "value" => $isNew ? null : $data["width3"],
                                    "events" => $events
                                ))
                                ->input("Textbox", array(
                                    "label" => "height",
                                    "infoKey" => "height3",
                                    "value" => $isNew ? null : $data["height3"],
                                    "events" => $events
                                ))
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->image("ImageList", array(
                    "height" => 50,
                    "images" => $isNew ? array() : $settings["vars"]["images"],
                    "events" => $events,
                    "index" => "imagelist"
                ))
                ->startGroup("LazyTabGroup");
        
        
        if($this->module("fileupload")->hasPermission("upload file")) {
            $this->framework
                ->startGroup("TitledSection", array("title" => "Upload"))
                    ->input("SimpleImageUploader", array(
                        "multiple" => true,
                        "eval" => "this.on('imageAdded', function(data) {
                            this.find('imagelist').addImage(data.src, data.id);
                        });"
                    ))
                    ->end();
        }
                
                $this->framework
                    ->startGroup("TitledSection", array("title" => "Images"))
                        ->list("DataPager", array(
                            "display" => array(
                                "name" => "ImageGrid",
                                "type" => "image",
                                "dirty" => "never",
                                "size" => 20,
                                "selectable" => false,
                                "eval" => "this.on('click', function(data) {
                                    this.find('imagelist').addImage(data.src, data.id);
                                });"
                            ),
                            "data" => array(
                                "event" => "getImages"
                            ),
                            "dataPerSite" => 5,
                            "validators" => array(
                                "selectOne" => true
                            )
                        ));
    }
}
?>
