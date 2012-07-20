<?php
/**
 * Module Prototype
 * 
 * PHP version 5.3
 * 
 * @author  Friedolin Förder <friedolin.foerder@pinion-cms.org>
 * @license MIT License
 * @link    http://www.pinion-cms.org/module/prototype
 */

namespace modules\prototype;

use \pinion\modules\Module;
use \pinion\modules\FrontendModule;
use \pinion\events\Event;
use \pinion\general\TemplateBuilder;

class Prototype extends Module {
    
    public function defineBackend() {
        
        $this->framework
            ->startGroup("LazyMainTab", array("title" => "Website"))
                ->html("IFrame", array("src" => "http://api.prototypejs.org/", "height" => "500px"));
        
    }
    
    protected function _run() {
        $this->response->addJs("prototype-1.7.js", "module:prototype");
    }
    
    public function menu() {
        return array(
            "development->javascript->libraries->prototype" => "Website"
        );
    }
        
}


?>