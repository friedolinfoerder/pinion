<?php
/**
 * Module Jqueryzoom
 * 
 * PHP version 5.3
 * 
 * @author  Friedolin Förder <friedolin.foerder@pinion-cms.org>
 * @license MIT License
 * @link    http://www.pinion-cms.org/module/jqueryzoom
 */

namespace modules\jqueryzoom;

use \pinion\modules\Module;
use \pinion\modules\FrontendModule;
use \pinion\events\Event;
use \pinion\general\TemplateBuilder;

class Jqueryzoom extends Module {
    
    
    public function defineBackend() {
        parent::defineBackend();
        
        $this->framework
            ->startGroup("LazyMainTab", array("title" => "Website"))
                ->html("IFrame", array("src" => "http://www.jacklmoore.com/zoom", "height" => "500px"));
    }
    
    protected function _run() {
        $this->module("jquery")->run();
        $this->response->addJs("jquery.zoom-min.js", "module:jqueryzoom");
    }
    
    public function menu() {
        return array(
            "development->javascript->jQuery zoom" => "Website"
        );
    }
}


?>