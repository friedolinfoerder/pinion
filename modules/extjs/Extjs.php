<?php
/**
 * Module Extjs
 * 
 * PHP version 5.3
 * 
 * @author  Friedolin Förder <friedolin.foerder@pinion-cms.org>
 * @license MIT License
 * @link    http://www.pinion-cms.org/module/extjs
 */

namespace modules\extjs;

use \pinion\modules\Module;
use \pinion\modules\FrontendModule;
use \pinion\events\Event;
use \pinion\general\TemplateBuilder;

class Extjs extends Module {
    
    
    public function defineBackend() {
        parent::defineBackend();
        
        $this->framework
            ->startGroup("LazyMainTab", array("title" => "Website"))
                ->html("IFrame", array("src" => "http://docs.sencha.com/core/", "height" => "500px"));
            
    }
    
    protected function _run() {
        $this->response->addJs("extjs-3.1.js", "module:extjs");
    }
    
    public function menu() {
        return array(
            "development->javascript->libraries->extjs" => "Website"
        );
    }
        
}


?>