<?php
/**
 * Module Jqueryui
 * 
 * PHP version 5.3
 * 
 * @author  Friedolin Förder <friedolin.foerder@pinion-cms.org>
 * @license MIT License
 * @link    http://www.pinion-cms.org/module/jqueryui
 */

namespace modules\jqueryui;

use \pinion\modules\Module;

class JqueryUI extends Module {
    
    public function init() {
        if($this->identity) {
            $this->run();
        
            $this->module("jQuery")->addPlugin("ui.nestedSortable", false);
        }
    }
    
    protected function _run() {
        $this->module("jquery")->run();
        $this->response->addJs("jquery-ui-latest.min.js", "module:jqueryui");
    }
    
    public function defineBackend() {
        
        $this->framework
            ->startGroup("LazyMainTab", array("title" => "Website"))
                ->html("IFrame", array("src" => "http://jqueryui.com/demos/", "height" => "500px"));
        
    }
    
    public function menu() {
        return array(
            "development->javascript->libraries->jQuery UI" => "Website"
        );
    }
}
?>
