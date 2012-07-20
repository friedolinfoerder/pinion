<?php
/**
 * Module Dojo
 * 
 * PHP version 5.3
 * 
 * @author  Friedolin Förder <friedolin.foerder@pinion-cms.org>
 * @license MIT License
 * @link    http://www.pinion-cms.org/module/dojo
 */

namespace modules\dojo;

use \pinion\modules\Module;
use \pinion\modules\FrontendModule;
use \pinion\events\Event;
use \pinion\general\TemplateBuilder;

class Dojo extends Module {
    
    
    public function defineBackend() {
        
        $this->framework
            ->startGroup("LazyMainTab", array("title" => "Website"))
                ->html("IFrame", array("src" => "http://dojotoolkit.org/documentation/", "height" => "500px"));
        
    }
    
    protected function _run() {
        $this->response->addJs("dojo.js", "module:dojo");
    }
    
    public function menu() {
        return array(
            "development->javascript->libraries->dojo" => "Website"
        );
    }
        
}


?>