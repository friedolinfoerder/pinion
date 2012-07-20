<?php
/**
 * Module Jquery
 * 
 * PHP version 5.3
 * 
 * @author  Friedolin Förder <friedolin.foerder@pinion-cms.org>
 * @license MIT License
 * @link    http://www.pinion-cms.org/module/jquery
 */

namespace modules\jquery;

use \pinion\modules\Module;

class Jquery extends Module {
    
    public function init() {
        if($this->identity) {
            $this->run();
            
            $this->response->addJs("jquery-latest.min.js", "module:jquery");
            $this
                ->addPlugin("json-2.3")
                ->addPlugin("cookie", false)
                ->addPlugin("mousewheel")
                ->addPlugin("jscrollpane")
                ->addPlugin("hive", false)
                ->addPlugin("hive.pollen", false);
        }
    }
    
    public function addPlugin($name, $minified = true) {
        $min = $minified ? ".min" : "";
        $this->response->addJs("plugins/jquery.$name$min.js", "module:jquery");
        
        // fluent interface
        return $this;
    }
    
    public function defineBackend() {
        
        $this->framework
            ->startGroup("LazyMainTab", array("title" => "Website"))
                ->html("IFrame", array("src" => "http://docs.jquery.com", "height" => "500px"));
        
    }
    
    protected function _run() {
        $this->response->addJs("jquery-latest.min.js", "module:jquery");
    }
    
    public function menu() {
        return array(
            "development->javascript->libraries->jQuery" => "Website"
        );
    }
}
?>
