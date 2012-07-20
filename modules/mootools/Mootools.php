<?php
/**
 * Module Mootools
 * 
 * PHP version 5.3
 * 
 * @author  Friedolin Förder <friedolin.foerder@pinion-cms.org>
 * @license MIT License
 * @link    http://www.pinion-cms.org/module/mootools
 */

namespace modules\mootools;

use \pinion\modules\Module;
use \pinion\modules\FrontendModule;
use \pinion\events\Event;
use \pinion\general\TemplateBuilder;

class Mootools extends Module {
    
    public function defineBackend() {
        parent::defineBackend();
        
        $this->framework
            ->startGroup("LazyMainTab", array("title" => "Website"))
                ->html("IFrame", array("src" => "http://mootools.net/docs/core", "height" => "500px"));
            
    }
    
    protected function _run() {
        $this->response->addJs("mootools-core-1.4.5.js", "module:mootools");
    }
    
    public function menu() {
        return array(
            "development->javascript->libraries->mootools" => "Website"
        );
    }
        
}


?>