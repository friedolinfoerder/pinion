<?php
/**
 * Module Nivoslideshow
 * 
 * PHP version 5.3
 * 
 * @author  Friedolin Förder <friedolin.foerder@pinion-cms.org>
 * @license MIT License
 * @link    http://www.pinion-cms.org/module/nivoslideshow
 */

namespace modules\nivoslideshow;

use \pinion\modules\Module;
use \pinion\modules\FrontendModule;
use \pinion\events\Event;

/**
 * Description of Nivoslideshow
 * 
 * @category   modules
 * @package    pinion
 * @subpackage modules
 * @author     Friedolin Förder <friedolinfoerder@pinion-cms.de>
 * @license    license placeholder
 * @link       http://www.pinion-cms.de
 */
class Nivoslideshow extends FrontendModule {
    
    public function install() {
        
        $this->data->createDataStorage("nivoslideshow", array(
            "width" => array("type" => "int", "isNull" => false),
            "height" => array("type" => "int", "isNull" => false),
            "preset" => "image"
        ));
        
        $this->data->createDataStorage("image", array(
            "position" => array("type" => "int"),
            "description" => array("type" => "varchar", "length" => 100),
            "file" => "image",
            "nivoslideshow"
        ));
        
        $this->data->options(null, array(
            "effect"                  => 'random', // Specify sets like => 'fold,fade,sliceDown'
            "slices"                  => 15, // For slice animations
            "boxCols"                 => 8, // For box animations
            "boxRows"                 => 4, // For box animations
            "animSpeed"               => 500, // Slide transition speed
            "pauseTime"               => 3000, // How long each slide will show
            "startSlide"              => 0, // Set starting Slide (0 index)
            "directionNav"            => true, // Next & Prev navigation
            "directionNavHide"        => true, // Only show on hover
            "controlNav"              => true, // 1,2,3... navigation
            "controlNavThumbs"        => false, // Use thumbnails for Control Nav
            "controlNavThumbsFromRel" => false, // Use image rel for thumbs
            "controlNavThumbsSearch"  => '.jpg', // Replace this with...
            "controlNavThumbsReplace" => '_thumb.jpg', // ...this in thumb Image src
            "keyboardNav"             => true, // Use left & right arrows
            "pauseOnHover"            => true, // Stop animation while hovering
            "manualAdvance"           => false, // Force manual transitions
            "captionOpacity"          => 0.8, // Universal caption opacity
            "prevText"                => 'Prev', // Prev directionNav text
            "nextText"                => 'Next', // Next directionNav text
            "randomStart"             => false, // Start on a random slide
        ), true);
    }
    
    public function getResources() {
        return array_merge(parent::getResources(), array(
            "edit options"
        ));
    }
    
    public function addListener() {
        parent::addListener();
        
        if($this->hasPermission("edit options"))       $this->addEventListener("changeOptions");
    }
    
    public function preview($data) {
        return $data->attributes(array(
            "*",
            "images" => function($ar) {
                $file = $ar->file;
                if($file) {
                    return array(
                        "id" => $file->id,
                        "filename" => $file->file->filename,
                    );
                }
            }
        ));
    }
    
    public function changeOptions(Event $event) {
        
        $options = array();
        $possibleOptions = array(
            "effect",
            "slices",
            "boxCols",
            "boxRows",
            "animSpeed",
            "pauseTime",
            "startSlide",
            "directionNav",
            "directionNavHide",
            "controlNav",
            "controlNavThumbs",
            "controlNavThumbsFromRel",
            "controlNavThumbsSearch",
            "controlNavThumbsReplace",
            "keyboardNav",
            "pauseOnHover",
            "manualAdvance",
            "captionOpacity",
            "prevText",
            "nextText",
            "randomStart"
        );
        
        foreach($possibleOptions as $possibleOption) {
            if($event->hasInfo($possibleOption)) {
                $options[$possibleOption] = $event->getInfo($possibleOption);
            }
        }
        
        $this->data->options(null, $options);
        
        // add success message
        $this->response->addSuccess($this, $this->translate("The options of the module %s were updated", $this->information["title"]));
    }
    
    public function add(Event $event) {
        
        $slideshowData = $this->data->create("nivoslideshow", array(
            "width"  => $event->getInfo("width"),
            "height" => $event->getInfo("height"),
            "preset_id" => $event->getInfo("preset")
        ));
        
        $images = $event->getInfo("images");
        $position = 0;
        
        if($images) {
            foreach($images as $image) {
                $this->data->create("image", array(
                    "position" => $position++,
                    "file_id" => $image["id"],
                    "nivoslideshow_id" => $slideshowData->id
                ));
            }
        } else {
            $this->response->addWarning($this, $this->translate(true, "no %s added", "images"));
        }
        
        $this->module("page")->setModuleContentMapping($event, $slideshowData);
        
        // add success message
        $this->response->addSuccess($this, $this->translate("%s added", "<b>".$this->translate($this->information["title"])."</b>"));
    }
    
    
    public function edit(Event $event) {
        $id = $event->getInfo("id");
        $images = $event->getInfo("images");
        
        $slideshowData = $this->data->find("nivoslideshow", $id);
        $imageDatas = $slideshowData->images;
        
        if($images) {
            foreach($imageDatas as $image) {
                $image->delete();
            }
            
            $position = 0;
            foreach($images as $image) {
                $this->data->create("image", array(
                    "position" => $position++,
                    "file_id"  => $image["id"],
                    "nivoslideshow_id" => $id
                ));
            }
        }
        
        if($event->hasInfo("width")) {
            $slideshowData->width = $event->getInfo("width");
        }
        if($event->hasInfo("height")) {
            $slideshowData->height = $event->getInfo("height");
        }
        if($event->hasInfo("preset")) {
            $slideshowData->preset_id = $event->getInfo("preset");
        }
        $slideshowData->save();
        
        // clear cache of table
        $slideshowData->table()->clear_cache();
        $slideshowData->reload();
        
        // dispatch content event to update the contents on the page
        $this->module("page")->dispatchEvent("content", array("data" => $slideshowData));
        
        // add success message
        $this->response->addSuccess($this, $this->translate("%s edited", "<b>".$this->translate($this->information["title"])."</b>"));
    }
    
    
    public function setFrontendVars($data) {
        
        $options = $this->data->options();
        
        $width = $data->width;
        $height =  $data->height;
        
        $images = $data->images;
        
        $srcs = array();
        $imgs = array();
        foreach($images as $image) {
            $src = $this->module("image")->getImage($image->file, array($data->preset), $width, $height);
            $srcs[] = $src;
            $imgs[] = array(
                "id"  => $image->file_id,
                "src" => $src
            );
        }
        
        // start jquery
        $this->module("jquery")->run();
        
        return array(
            "id" => "nivoslideshow-".\time().$data->id,
            "options" => $options,
            "optionsJson" => json_encode($options),
            "slideshow" => "Hier ist die Slideshow!",
            "sources" => $srcs,
            "images" => $imgs,
            "width" => $width,
            "height" => $height
        );
    }
    
    public function defineBackend() {
        parent::defineBackend();
        
        $options = $this->data->options();
        
        if($this->hasPermission("edit options")) {
            $this->framework
                ->startGroup("LazyMainTab", array("title" => "Options", "groupEvents" => true));

            $this->framework
                ->startGroup("TitledGroup", array("title" => "Animation", "open" => false));

            $this->framework
                ->list("Selector", array(
                    "label"   => "Effect",
                    "help"    => "What <b>transition</b> should be used",
                    "infoKey" => "effect",
                    "value"   => $options["effect"],
                    "noEmptyValue" => true,
                    "data"    => array(
                        array(
                            "id"   => 'random',
                            "name" => 'random'
                        ),
                        array(
                            "id"   => 'sliceDownRight',
                            "name" => 'sliceDownRight'
                        ),
                        array(
                            "id"   => 'sliceDownLeft',
                            "name" => 'sliceDownLeft'
                        ),
                        array(
                            "id"   => 'sliceUpRight',
                            "name" => 'sliceUpRight'
                        ),
                        array(
                            "id"   => 'sliceUpLeft',
                            "name" => 'sliceUpLeft'
                        ),
                        array(
                            "id"   => 'sliceUpDown',
                            "name" => 'sliceUpDown'
                        ),
                        array(
                            "id"   => 'sliceUpDownLeft',
                            "name" => 'sliceUpDownLeft'
                        ),
                        array(
                            "id"   => 'fold',
                            "name" => 'fold'
                        ),
                        array(
                            "id"   => 'fade',
                            "name" => 'fade'
                        ),
                        array(
                            "id"   => 'boxRandom',
                            "name" => 'boxRandom'
                        ),
                        array(
                            "id"   => 'boxRain',
                            "name" => 'boxRain'
                        ),
                        array(
                            "id"   => 'boxRainReverse',
                            "name" => 'boxRainReverse'
                        ),
                        array(
                            "id"   => 'boxRainGrow',
                            "name" => 'boxRainGrow'
                        ),
                        array(
                            "id"   => 'boxRainGrowReverse',
                            "name" => 'boxRainGrowReverse'
                        )
                    ),
                    "events"  => array("event" => "changeOptions")
                ));

            $this->framework
                ->input("Textbox", array(
                    "label"   => "Slices",
                    "help"    => "How many <b>slices</b> should be created for splice animations",
                    "infoKey" => "slices",
                    "value"   => $options["slices"],
                    "events"  => array("event" => "changeOptions")
                ));

            $this->framework
                ->input("Textbox", array(
                    "label"   => "Number of columns",
                    "help"    => "How many <b>vertical boxes</b> should be created for animations",
                    "infoKey" => "boxCols",
                    "value"   => $options["boxCols"],
                    "events"  => array("event" => "changeOptions")
                ));

            $this->framework
                ->input("Textbox", array(
                    "label"   => "Number of rows",
                    "help"    => "How many <b>horizontal boxes</b> should be created for animations",
                    "infoKey" => "boxRows",
                    "value"   => $options["boxRows"],
                    "events"  => array("event" => "changeOptions")
                ));

            $this->framework
                ->input("Textbox", array(
                    "label"   => "Speed of the animation",
                    "help"    => "How fast will the animation be process",
                    "infoKey" => "animSpeed",
                    "value"   => $options["animSpeed"],
                    "events"  => array("event" => "changeOptions")
                ));

            $this->framework
                ->input("Textbox", array(
                    "label"   => "Slide to start with",
                    "help"    => "What slide should be used for the start",
                    "infoKey" => "startSlide",
                    "value"   => $options["startSlide"],
                    "events"  => array("event" => "changeOptions")
                ));

            $this->framework
                ->end()
                ->startGroup("TitledGroup", array("title" => "Navigation", "open" => false));

            $this->framework
                ->input("Checkbox", array(
                    "label"   => "Direction of the navigation",
                    "help"    => "In which direction should the navigation be aligned",
                    "infoKey" => "directionNav",
                    "value"   => $options["directionNav"],
                    "events"  => array("event" => "changeOptions")
                ));

            $this->framework
                ->input("Checkbox", array(
                    "label"   => "Hide the navigation",
                    "help"    => "Should the navigation be hidden?",
                    "infoKey" => "directionNavHide",
                    "value"   => $options["directionNavHide"],
                    "events"  => array("event" => "changeOptions")
                ));

            $this->framework
                ->input("Checkbox", array(
                    "label"   => "Control the navigation",
                    "help"    => "Should the navigation be controlled with a list?",
                    "infoKey" => "controlNav",
                    "value"   => $options["controlNav"],
                    "events"  => array("event" => "changeOptions")
                ));

            $this->framework
                ->input("Checkbox", array(
                    "label"   => "Control navigation with keyboard",
                    "help"    => "Should the navigation be controlled with the left and right arrows of the keyboard",
                    "infoKey" => "keyboardNav",
                    "value"   => $options["keyboardNav"],
                    "events"  => array("event" => "changeOptions")
                ));

            $this->framework
                ->startGroup("TitledGroup", array("title" => "Thumbnails", "open" => false));

            $this->framework
                ->input("Checkbox", array(
                    "label"   => "Thumbnails for navigation",
                    "help"    => "Use thumbnails to control the navigation",
                    "infoKey" => "controlNavThumbs",
                    "value"   => $options["controlNavThumbs"],
                    "events"  => array("event" => "changeOptions")
                ));

            $this->framework
                ->input("Checkbox", array(
                    "label"   => "Use rel tag for thumbnails",
                    "help"    => "Use image <i>rel</i> tag for the thumbnails",
                    "infoKey" => "controlNavThumbsFromRel",
                    "value"   => $options["controlNavThumbsFromRel"],
                    "events"  => array("event" => "changeOptions")
                ));

            $this->framework
                ->input("Textbox", array(
                    "label"   => "Search string",
                    "help"    => "Replace this string with the replace string (next input)",
                    "infoKey" => "controlNavThumbsSearch",
                    "value"   => $options["controlNavThumbsSearch"],
                    "events"  => array("event" => "changeOptions")
                ));

            $this->framework
                ->input("Textbox", array(
                    "label"   => "Replace string",
                    "help"    => "The search string (previous input) will be replaced with this string",
                    "infoKey" => "controlNavThumbsReplace",
                    "value"   => $options["controlNavThumbsReplace"],
                    "events"  => array("event" => "changeOptions")
                ));



            $this->framework
                ->end()
                ->end()
                ->startGroup("TitledGroup", array("title" => "Behavior", "open" => false));

            $this->framework
                ->input("Checkbox", array(
                    "label"   => "Random start",
                    "help"    => "Start on a random slide",
                    "infoKey" => "randomStart",
                    "value"   => $options["randomStart"],
                    "events"  => array("event" => "changeOptions")
                ));

            $this->framework
                ->input("Checkbox", array(
                    "label"   => "Pause on hover",
                    "help"    => "Stop animation while hovering",
                    "infoKey" => "pauseOnHover",
                    "value"   => $options["pauseOnHover"],
                    "events"  => array("event" => "changeOptions")
                ));

            $this->framework
                ->input("Checkbox", array(
                    "label"   => "Manual advance",
                    "help"    => "Force manual transitions",
                    "infoKey" => "manualAdvance",
                    "value"   => $options["manualAdvance"],
                    "events"  => array("event" => "changeOptions")
                ));

            $this->framework
                ->end()
                ->startGroup("TitledGroup", array("title" => "Appearance", "open" => false));

            $this->framework
                ->input("Textbox", array(
                    "label"   => "Caption opacity",
                    "help"    => "Universal caption opacity",
                    "infoKey" => "captionOpacity",
                    "value"   => $options["captionOpacity"],
                    "events"  => array("event" => "changeOptions")
                ));

            $this->framework
                ->input("Textbox", array(
                    "label"   => "Previous text",
                    "help"    => "<i>Previous</i> navigation text",
                    "infoKey" => "prevText",
                    "value"   => $options["prevText"],
                    "events"  => array("event" => "changeOptions")
                ));

            $this->framework
                ->input("Textbox", array(
                    "label"   => "Next text",
                    "help"    => "<i>Next</i> navigation text",
                    "infoKey" => "nextText",
                    "value"   => $options["nextText"],
                    "events"  => array("event" => "changeOptions")
                ));
        }
        
    }
    
    
    public function defineEditor(Event $event) {
        $settings = $event->getInfo("settings");
        $data = $settings["data"];
        $events = $settings["events"];
        $isNew = $settings["_isNew"];
        
        $this->framework
            ->key("elements")
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
                ->list("Selector", array(
                    "label" => "preset",
                    "value" => $settings["_isNew"] ? null : $settings["data"]["preset_id"],
                    "events" => $settings["events"],
                    "data" => array(
                        "event" => "getPresets",
                        "module" => "image"
                    )
                ))
                ->image("ImageList", array(
                    "height" => 50,
                    "images" => $isNew ? array() : $settings["vars"]["images"],
                    "events" => $events,
                    "fire" => array(
                        "event" => "ImageListCreated",
                        "module" => "image"
                    ),
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
                                "event" => "getImages",
                                "module" => "image"
                            ),
                            "dataPerSite" => 5,
                            "validators" => array(
                                "selectOne" => true
                            )
                        ));
    }
}

?>
