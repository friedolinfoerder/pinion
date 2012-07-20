<?php
/**
 * Module Googleanalytics
 * 
 * PHP version 5.3
 * 
 * @author  Friedolin Förder <friedolin.foerder@pinion-cms.org>
 * @license MIT License
 * @link    http://www.pinion-cms.org/module/googleanalytics
 */

namespace modules\googleanalytics;

use \pinion\modules\Module;
use \pinion\modules\FrontendModule;
use \pinion\events\Event;

class Googleanalytics extends Module {
    
    public function install() {
        $this->data
            ->options(null, array(
                "account"     => null,
                "anonymizeIp" => true
            ), true);
    }
    
    public function init() {
        if(!$this->identity) {
            $options = $this->data->options();
            $account = $options["account"];
            if($account) {
                $settings = array();
                
                
                // SET SETTINGS
                $settings[] = "'_setAccount', '$account'";
                if($options["anonymizeIp"]) {
                    $settings[] = "'_gat._anonymizeIp'";
                }
                $settings[] = "'_trackPageview'";
                
                
                
                foreach($settings as &$setting) {
                    $setting = "_gaq.push([$setting]);";
                }
                $settings = join("\n", $settings);
                $script = <<<SCRIPT
var _gaq = _gaq || [];
$settings

(function() {
    var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
    ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
    var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
})();
SCRIPT;
                $this->response->addJsCode($script);
            }
        }
    }
    
    public function addListener() {
        parent::addListener();
        
        if($this->hasPermission("edit options"))       $this->addEventListener("changeOptions");
    }
    
    public function getResources() {
        return array_merge(parent::getResources(), array(
            "edit options"
        ));
    }
    
    public function changeOptions(Event $event) {
        
        $options = array();
        $possibleOptions = array(
            "account",
            "anonymizeIp"
        );
        
        foreach($possibleOptions as $possibleOption) {
            if($event->hasInfo($possibleOption)) {
                $options[$possibleOption] = $event->getInfo($possibleOption);
            }
        }
        if(isset($options["account"]) && trim($options["account"]) == "") {
            $options["account"] = null;
        }
        
        $this->data->options(null, $options);
        
        // add success message
        $this->response->addSuccess($this, $this->translate("The options of the module %s were updated", $this->information["title"]));
    }
    
    public function defineBackend() {
        parent::defineBackend();
        
        $options = $this->data->options();
        
        if($this->hasPermission("edit options")) {
            $this->framework
                ->startGroup("LazyMainTab", array("title" => "Options", "groupEvents" => true))
                    ->input("Textbox", array(
                        "label"   => "account id",
                        "help"    => "The google analytics account id",
                        "infoKey" => "account",
                        "value"   => $options["account"],
                        "events"  => array("event" => "changeOptions")
                    ))
                    ->input("Checkbox", array(
                        "label"   => "anonymize ip",
                        "help"    => "Should google analytics anonymize the ip addresses of the visitors of the page",
                        "infoKey" => "anonymizeIp",
                        "value"   => $options["anonymizeIp"],
                        "events"  => array("event" => "changeOptions")
                    ));
        }
        
            
    }
    
    
}


?>