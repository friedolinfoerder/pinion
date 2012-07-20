<?php
/**
 * Module Colorbox
 * 
 * PHP version 5.3
 * 
 * @author  Friedolin Förder <friedolin.foerder@pinion-cms.org>
 * @license MIT License
 * @link    http://www.pinion-cms.org/module/colorbox
 */

namespace modules\colorbox;

use \pinion\modules\Module;
use \pinion\modules\FrontendModule;
use \pinion\events\Event;
use \pinion\general\TemplateBuilder;

class Colorbox extends Module {
    
    
    protected function _run() {
        $this->module("jquery")->run();
        $this->response
            ->addCss("colorbox.css", "module:colorbox")
            ->addJs("jquery.colorbox-min.js", "module:colorbox");
    }
    
    public function defineBackend() {
        parent::defineBackend();
        
        $this->framework
            ->text("Headline", array("text" => "This is the backend area of the module \"Colorbox\""));
            
    }
    
    
}


?>