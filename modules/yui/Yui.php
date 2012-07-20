<?php
/**
 * Module Yui
 * 
 * PHP version 5.3
 * 
 * @author  Friedolin Förder <friedolin.foerder@pinion-cms.org>
 * @license MIT License
 * @link    http://www.pinion-cms.org/module/yui
 */

namespace modules\yui;

use \pinion\modules\Module;
use \pinion\modules\FrontendModule;
use \pinion\events\Event;
use \pinion\general\TemplateBuilder;

class Yui extends Module {
    
    
    public function defineBackend() {
        
        $this->framework
            ->startGroup("LazyMainTab", array("title" => "Website"))
                ->html("IFrame", array("src" => "http://yuilibrary.com/yui/docs/", "height" => "500px"));
        
    }
    
    protected function _run() {
        $this->response->addJs("yui-3.5.js", "module:yui");
    }
    
    public function menu() {
        return array(
            "development->javascript->libraries->yui" => "Website"
        );
    }
        
}


?>