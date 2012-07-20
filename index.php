<?php
/**
 * pinion cms 0.5
 * 
 * PHP version 5.3
 * 
 * @author  Friedolin Förder <friedolin.foerder@pinion-cms.org>
 * @author  Andreas Dorschner <andreas.dorschner@pinion-cms.org>
 * @license MIT License
 * @link    http://www.pinion-cms.org
 */

//usage
use pinion\general\Registry;

// bootrap the pinion cms
require_once 'bootstrap.php';


// getting and setting the timezone
$timezoneOffset = $request->getPostParameter("timezoneOffset");
if($timezoneOffset) {
    $timezoneOffset = (int)$timezoneOffset;
    $session->setParameter("timezoneOffset", $timezoneOffset, 60*60*24*365); // cookie for one year
    $timezoneOffsetString = ($timezoneOffset >= 0) ? "-$timezoneOffset" : "+".(-$timezoneOffset);
    print "timezone set to GMT$timezoneOffsetString.";
    return;
}

$timezoneOffset = $session->getParameter("timezoneOffset");
if($timezoneOffset) {
    $timezoneOffset = (int)$timezoneOffset;
    $timezoneOffsetString = ($timezoneOffset >= 0) ? "+$timezoneOffset" : "$timezoneOffset"; 
    date_default_timezone_set("Etc/GMT$timezoneOffsetString");
} else {
    date_default_timezone_set(Registry::getTimezone());   
}



// start all modules
$moduleManager->startModules();

// generate output
if($request->isAjax()) {
    $postFile = file_get_contents('php://input');
    $events = json_decode($postFile, true);
    $events = $events["events"];
    
    if($events && is_array($events)) {
        foreach($events as $eventData) {
            $module = $eventData["module"];
            $event = $eventData["event"];
            $info = (isset($eventData["info"]) && is_array($eventData["info"])) ? $eventData["info"] : array();
            if(is_string($module) && is_string($event)) {
                
                $module = $moduleManager->module($module);
                if($module) {
                    // set info for the backend event
                    if($event == "backend" && !empty($info)) {
                        $response->setInfo("backend.info", $info);
                    }
                    // dispatch event of module
                    $module->dispatchEvent($event, $info);
                }
            }
        }
    } else {
        $events = $request->getRequestParameter("events");
        
        if($events) {
            if(get_magic_quotes_gpc()) {
                $events = stripslashes($events);
            }
            $events = json_decode($events, true);
            if($events && is_array($events)) {
                foreach($events as $eventData) {
                    $module = $eventData["module"];
                    $event = $eventData["event"];
                    $info = (isset($eventData["info"]) && is_array($eventData["info"])) ? $eventData["info"] : array();
                    if(is_string($module) && is_string($event)) {
                        
                        $module = $moduleManager->module($module);
                        if($module) {
                            // set info for the backend event
                            if($event == "backend" && !empty($info)) {
                                $response->setInfo("backend.info", $info);
                            }
                            // dispatch event of module
                            $module->dispatchEvent($event, $info);
                        }
                    }
                }
            } 
        } else {
            $event = $request->getRequestParameter("event");
            if($event) {
                $module = $request->getRequestParameter("module");
                if($module) {

                    $module = $moduleManager->module($module);
                    if($module) {
                        // dispatch event of module
                        $module->dispatchEvent($event, $request->getRequest());
                    }
                }
            }
        }
        
    }
    // print json
    $response->flushInfos();
} else {
    
    // dispatch events
    if($request->hasRequestParameter("event", "module")) {
        $event = $request->getRequestParameter("event");
        $module = $request->getRequestParameter("module");
        
        $module = $moduleManager->module($module);
        if($module) {
            // dispatch event of module
            $module->dispatchEvent($event, $request->getRequest());
        }
    }
        
    
    if($identity) {
        if($identity->rule == "Administrators" && $identity->permissions == null) {
            // load the resources for the Administrators
            $moduleManager->reloadAdministrationResources();
        }
        
        $shortcuts = json_decode($identity->shortcuts);
        $shortcutsInformations = array();
        foreach($shortcuts as $shortcut) {
            $module = $moduleManager->module($shortcut);
            if($module && $module->hasPermission("backend")) {
                $shortcutsInformations[] = array(
                    "name"  => $shortcut,
                    "title" => $module->information["title"],
                    "icon"  => $moduleManager::getIcon($shortcut)
                );
            }
        }
        
        $response
            ->addJs("pinion/js/core.min.js")
            ->addJsVariable("url", SITE_URL)
            ->addJsVariable("pinionUrl", PINION_URL)
            ->addJsVariable("modulesUrl", MODULES_URL)
            ->addJsVariable("timezoneOffset", ($timezoneOffset !== null) ? $timezoneOffset : "null")
            ->addJsVariable("updateInterval", Registry::getUpdateInterval())
            ->addJsVariable("shortcuts", $shortcutsInformations)
            ->addJsVariable("language", $identity->language)
            ->addJsVariable("rule", $identity->rule)
            ->addJsVariable("userid", $identity->userid)
            ->addJsVariable("permissions", $identity->permissions)
            ->addJsVariable("user", $moduleManager->module("permissions")->getOnlineUser())
            ->addJsVariable("supportedLanguages", Registry::getSupportedLanguages());
    }
    
    if($moduleManager->module("page")) {
        $frontController
            ->showPage($request, $response, $session, $identity);
    }
    // print html
    $response->flush();
}

?>