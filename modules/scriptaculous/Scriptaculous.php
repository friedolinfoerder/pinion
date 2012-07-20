<?php
/**
 * Module Scriptaculous
 * 
 * PHP version 5.3
 * 
 * @author  Friedolin Förder <friedolin.foerder@pinion-cms.org>
 * @license MIT License
 * @link    http://www.pinion-cms.org/module/scriptaculous
 */

namespace modules\scriptaculous;

use \pinion\modules\Module;
use \pinion\modules\FrontendModule;
use \pinion\events\Event;
use \pinion\general\TemplateBuilder;

class Scriptaculous extends Module {
    
    
    public function defineBackend() {
        
        $this->framework
            ->startGroup("LazyMainTab", array("title" => "Website"))
                ->html("IFrame", array("src" => "http://madrobby.github.com/scriptaculous/", "height" => "500px"));
        
    }
    
    protected function _run() {
        $this->module("prototype")->run();
        $this->response->addJs("scriptaculous-1.9.js", "module:scriptaculous");
    }
    
    public function menu() {
        return array(
            "development->javascript->libraries->script.aculo.us" => "Website"
        );
    }
        
}


?>