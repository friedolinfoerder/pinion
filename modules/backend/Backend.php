<?php
/**
 * Module Backend
 * 
 * PHP version 5.3
 * 
 * @author  Friedolin Förder <friedolin.foerder@pinion-cms.org>
 * @license MIT License
 * @link    http://www.pinion-cms.org/module/backend
 */

namespace modules\backend;

use \pinion\modules\Module;
use \pinion\modules\Renderer;
use \pinion\events\Event;
use \pinion\modules\ModuleManager;

class Backend extends Module implements Renderer {
    
    public function install() {
        $this->setTranslation("de", "translations.php");
        
        $this->data->createDataStorage("option", array(
            "module" => array("type" => "varchar", "length" => 100),
            "key"    => array("type" => "varchar", "length" => 100),
            "value"  => array("type" => "text", "translatable" => false)
        ));
    }
    
    public $testCollection;
    
    public function init() {
        $this->framework->addCollection("TestCollection", array(
            0 => array(
                "type" => "startGroup",
                "name" => "TitledGroup",
                "title" => "TitledGroup",
                "open" => false
            ),
                1 => array(
                    "type"  => "startGroup",
                    "name"  => "TitledGroup",
                    "title" => "Headline of the TitledGroup"
                ),
                    2 => array(
                        "type" => "html",
                        "name" => "SimpleHtml",
                        "html" => "Here are the elements in this group."
                    ),
                    3 => array(
                        "type" => "end"
                    ),
                4 => array(
                    "type" => "end"
                )   
        ));
        
        $this->testCollection = array(
            0 => array(
                "type" => "startGroup",
                "name" => "TitledGroup",
                "title" => "TitledGroup",
                "open" => false
            ),
                1 => array(
                    "type"  => "startGroup",
                    "name"  => "TitledGroup",
                    "title" => "Headline of the TitledGroup"
                ),
                    2 => array(
                        "type" => "html",
                        "name" => "SimpleHtml",
                        "html" => "Here are the elements in this group."
                    ),
                    3 => array(
                        "type" => "end"
                    ),
                4 => array(
                    "type" => "end"
                )   
        );
    }
    
    public function addListener() {
        parent::addListener();
        
        self::$moduleManager->addEventListener("uninstall", "removeOptions", $this);
        
        if($this->identity) {
            $this->addEventListener("getExampleData");
            $this->addEventListener("getPagerFinderData");
            $this->addEventListener("getAjaxElements");
            $this->addEventListener("getAjaxSearchElements");
            $this->addEventListener("getRecursiveElements");
            
            $this->addEventListener("closeBackend");
        }
    }
    
    public function getRecursiveElements(Event $event) {
        $this->framework
            ->key("elements")
            ->startGroup("LazyTitledGroup", array("title" => "recursion"))
                ->text("Headline", array(
                    "text" => "for recursion click on the title of the group"
                ))
                ->startGroup("AjaxSection", array(
                    "data" => array("event" => "getRecursiveElements")
                ));
    }
    
    public function closeBackend(Event $event) {
        $this->session->unsetParameter("currentBackendPage");
    }
    
    public function removeOptions(Event $event) {
        $options = $this->data->find_all_by_module("option", $event->getInfo("module"));
        foreach($options as $option) {
            $option->delete(true);
        }
    }
    
    public function getExampleData(Event $event) {
        
        $this->response->setInfo("data", array(
            "ActionScript",
            "AppleScript",
            "Asp",
            "BASIC",
            "C",
            "C++",
            "Clojure",
            "COBOL",
            "ColdFusion",
            "Erlang",
            "Fortran",
            "Groovy",
            "Haskell",
            "Java",
            "JavaScript",
            "Lisp",
            "Perl",
            "PHP",
            "Python",
            "Ruby",
            "Scala",
            "Scheme"
        ));
    }
    
    
    public function getAjaxElements(Event $event) {
        
        // create fake data
        $data = array();
        for($i = 5000; $i--; ) {
            $data[] = array(
                "id" => $i,
                "hash" => md5("$i")
            );
        }
        
        $this->framework
            ->key("elements")
                ->list("DataPager", array(
                    "group" => array(
                        "StepGroup"
                    ),
                    "data" => $data
                ));     
    }
    
    public function getAjaxSearchElements(Event $event) {
        
        $hash = $event->getInfo("hash", "");
        $count = $event->getInfo("count", 250);
        $this->response->setInfo("count", $count);
        
        // create fake data
        $data = array();
        $currentCount = 0;
        for($i = 5000; $i--; ) {
            $d = array(
                "id" => $i,
                "hash" => md5("$i")
            );
            if(trim($hash) == "" || strpos($d["hash"], $hash) !== false) {
                $data[] = $d;
                if(++$currentCount >= $count) {
                    break;
                }
            }
        }
        
        if(empty($data)) {
            $this->framework
                ->key("elements")
                    ->html("SimpleHtml", array("html" => "No contents"));
        } else {
            $this->framework
                ->key("elements")
                    ->list("DataPager", array(
                        "group" => array(
                            "StepGroup"
                        ),
                        "data" => $data
                    )); 
        }
            
    }
    
    
    public function getPagerFinderData(Event $event) {
        
        $start = $event->getInfo("start");
        $end   = $event->getInfo("end");
        
        $return = array();
        $length = 1722;
        
        $end = min(array($length, $end));
        
        for($i = $start; $i < $end; $i++) {
            $j = $i+1;
            $return[] = array(
                "id" => $i,
                "name" => "Data #$j"
            );
        }
        
        $this->response->setInfo("data", $return);
        
        if($start == 0) {
            $this->response->setInfo("dataLength", $length);
        }
    }

    public function setFrontendVars($data) {
        if(!$this->identity) {
            // run jqueryui
            $this->module("jqueryui")->run();
            
            $this->response
                    ->addCss("login", "module:backend")
                    ->addJs("login", "module:backend");
            
            return array(
                "isLoggedIn" => false,
                "isWrong"    => $this->request->hasPostParameter("username"),
                "login"      => $this->request->getGetParameter("login")
            );
        } else {
            $usableModules = self::$moduleManager->getUsableModules();
            $moduleContainer = self::$moduleManager->getModuleContainer();
            $translatedAllWord = $this->translate("all");
            $translatedCoreWord = $this->translate("core");
            $modulesForMenu = array($translatedAllWord => array(), $translatedCoreWord => array());
            $menuItemsForMenu = array();
            $atLeastOneModule = false;
            
            $modulesForJS = array();
            
            foreach($moduleContainer as $modulename => $container) {
                
                $icon = ModuleManager::getIcon($modulename);
                $title = $this->translate($container->getTitle());
                
                $usableModule = $this->module($modulename);
                
                if($usableModule && $usableModule->hasPermission("backend")) {
                    
                    // menu items
                    $menuitems = $usableModule->menu();
                    if(!empty($menuitems)) {
                        foreach($menuitems as $key => $value) {
                            if(!is_string($key)) {
                                $key = $value;
                                $key = explode("->", $key);
                                $value = $usableModule->name."#".end($key);
                            } else {
                                $key = explode("->", $key);
                                $value = $usableModule->name."#".$value;
                            }
                            
                            // go through all menu path items and generate an array
                            $tempMenu = &$menuItemsForMenu;
                            while(count($key) > 1) {
                                $menuPath = array_shift($key);
                                $menuPath = $this->translate($menuPath);
                                if(!isset($tempMenu[$menuPath])) {
                                    $tempMenu[$menuPath] = array();
                                }
                                $tempMenu = &$tempMenu[$menuPath];
                            }
                            $tempMenu[$this->translate($key[0])] = array(
                                "href" => $value,
                                "icon" => $icon
                            );
                        }
                    }
                    
                    // module
                    $atLeastOneModule = true;
                    $category = $this->translate($usableModule->information["category"]);
                    if(!isset($modulesForMenu[$category])) {
                        $modulesForMenu[$category] = array();
                    }
                    $moduleInfoArray = array(
                        "name"  => $usableModule->name,
                        "title" => $title,
                        "imageSrc" => ModuleManager::getIcon($usableModule)
                    );
                    $modulesForMenu[$category][] = $moduleInfoArray;
                    $modulesForMenu[$translatedAllWord][] = $moduleInfoArray;
                    if($usableModule->information["core"]) {
                        $modulesForMenu[$translatedCoreWord][] = $moduleInfoArray;
                    }
                }
                $modulesForJS[$modulename] = array(
                    "title" => $title,
                    "icon"  => $icon,
                    "isUsable" => !is_null($usableModule),
                    "isFrontend" => ($usableModule instanceof \pinion\modules\FrontendModule)
                );
            }
            
            $this->response->addJsVariable("modules", $modulesForJS);
            
            return array(
                "url"  => SITE_URL.$this->request->getGetParameter("page"),
                "menuString"     => $this->translate("menu"),
                "modulesString"  => $this->translate("modules"),
                "identity"       => $this->identity,
                "modulesForMenu" => $atLeastOneModule ? $modulesForMenu : null,
                "menuItems"      => $menuItemsForMenu,
                "isLoggedIn"     => true
            );
        }
        
    }
    
    protected $_backendArray;
    
    protected function saveArray($array) {
        $this->_backendArray = $array;
        return $array;
    }
    
    protected function getLastArray() {
        return $this->_backendArray;
    }
    
    public function defineBackend() {
        
        $autoCompleteData = array(
            "ActionScript",
            "AppleScript",
            "Asp",
            "BASIC",
            "C",
            "C++",
            "Clojure",
            "COBOL",
            "ColdFusion",
            "Erlang",
            "Fortran",
            "Groovy",
            "Haskell",
            "Java",
            "JavaScript",
            "Lisp",
            "Perl",
            "PHP",
            "Python",
            "Ruby",
            "Scala",
            "Scheme"
        );
        
        $docuFramework = new DocuFramework($this->framework);
        
        $docuFramework
            ->startGroup("LazyMainTab", array("title" => "Examples"))
                
                // GROUP (with starting context)
                ->startTypeDocu("startGroup")
                
                    // TitledGroup
                    ->startElementDocu("TitledGroup")
                        ->startExample("Basics", array("title" => "Headline of TitledGroup"))
                            ->html("SimpleHtml", array("html" => "Here are the elements in this group."))
                            ->endExample()
                        ->endElementDocu()
                
                    // LazyTitledGroup
                    ->startElementDocu("LazyTitledGroup")
                        ->startExample("Basics", array("title" => "Headline of the LazyTitledGroup"))
                            ->html("SimpleHtml", array("html" => "Here are the elements in this group."))
                            ->endExample()
                        ->endElementDocu()
                
                    // AjaxTitledGroup
                    ->startElementDocu("AjaxTitledGroup")
                        ->example("Basics", array(
                            "title" => "Headline of the AjaxTitledGroup",
                            "data" => array(
                                "event" => "getAjaxElements"
                            )
                        ))
                        ->example("recursive", array(
                            "title" => "recursion",
                            "data" => array(
                                "event" => "getRecursiveElements"
                            )
                        ))
                        ->endElementDocu()
                
                    // TitledSection
                    ->startElementDocu("TitledSection")
                        ->startExample("Basics", array("title" => "Headline of the TitledSection"))
                            ->html("SimpleHtml", array("html" => "Here are the elements in this group."))
                            ->endExample()
                        ->endElementDocu()
                
                    // AjaxTitledSection
                    ->startElementDocu("AjaxTitledSection")
                        ->example("Basics", array(
                            "title" => "Headline of the AjaxTitledSection",
                            "data" => array(
                                "event" => "getAjaxElements"
                            )
                        ))
                        ->endElementDocu()
                
                    // Section
                    ->startElementDocu("Section")
                        ->startExample("Basics")
                            ->html("SimpleHtml", array("html" => "Here are the elements in this group."))
                            ->endExample()
                        ->endElementDocu()
                
                    // AjaxSearchSection
                    ->startElementDocu("AjaxSearchSection")
                        ->startExample("Basics")
                            ->input("Textbox", array(
                                "label" => "hash",
                                "events" => array(
                                    "event" => "getAjaxSearchElements"
                                )
                            ))
                            ->input("Slider", array(
                                "label" => "count",
                                "min" => 1,
                                "max" => 500,
                                "value" => 250,
                                "events" => array(
                                    "event" => "getAjaxSearchElements"
                                )
                            ))
                            ->endExample()
                        ->endElementDocu()
                
                    // Section
                    ->startElementDocu("AjaxSection")
                        ->example("Basics", array(
                            "data" => array(
                                "event" => "getAjaxElements"
                            )
                        ))
                        ->endElementDocu()
                    
                    // ColumnGroup
                    ->startElementDocu("ColumnGroup")
                        ->startExample("without splitting")
                            ->html("SimpleHtml", array("html" => "Column 1"))
                            ->html("SimpleHtml", array("html" => "Column 2"))
                            ->html("SimpleHtml", array("html" => "Column 3"))
                            ->endExample()
                        ->startExample("with splitting", array("splitting" => "1,8"))
                            ->html("SimpleHtml", array("html" => "Column 1"))
                            ->html("SimpleHtml", array("html" => "Column 2"))
                            ->html("SimpleHtml", array("html" => "Column 3"))
                            ->endExample()
                        ->endElementDocu()
                    
                    // Accordion
                    ->startElementDocu("Accordion")
                        ->startExample("Basics")
                            ->startGroup("TitledGroup", array("title" => "First tab (TitledGroup)"))
                                ->html("SimpleHtml", array("html" => "Here are the elements of the first tab."))
                                ->end() // end TitledGroup
                            ->startGroup("TitledGroup", array("title" => "Second tab (TitledGroup)"))
                                ->html("SimpleHtml", array("html" => "Here are the elements of the second tab."))
                                ->end() // end TitledGroup
                            ->startGroup("TitledGroup", array("title" => "Third tab (TitledGroup)"))
                                ->html("SimpleHtml", array("html" => "Here are the elements of the third tab."))
                                ->end() // end TitledGroup
                            ->endExample()
                        ->endElementDocu()
                
                    // TabGroup  
                    ->startElementDocu("TabGroup")
                        ->startExample("default")
                            ->startGroup("TitledSection", array("title" => "First tab (TitledSection)"))
                                ->html("SimpleHtml", array("html" => "Here are the elements of the first tab."))
                                ->end() // end TitledSection
                            ->startGroup("TitledSection", array("title" => "Second tab (TitledSection)"))
                                ->html("SimpleHtml", array("html" => "Here are the elements of the second tab."))
                                ->end() // end TitledSection
                            ->startGroup("TitledSection", array("title" => "Third tab (TitledSection)"))
                                ->html("SimpleHtml", array("html" => "Here are the elements of the third tab."))
                                ->end() // end TitledSection
                            ->endExample()
                        ->startExample("tabs at the bottom", array("tabPosition" => "bottom"))
                            ->startGroup("TitledSection", array("title" => "First tab (TitledSection)"))
                                ->html("SimpleHtml", array("html" => "Here are the elements of the first tab."))
                                ->end() // end TitledSection
                            ->startGroup("TitledSection", array("title" => "Second tab (TitledSection)"))
                                ->html("SimpleHtml", array("html" => "Here are the elements of the second tab."))
                                ->end() // end TitledSection
                            ->startGroup("TitledSection", array("title" => "Third tab (TitledSection)"))
                                ->html("SimpleHtml", array("html" => "Here are the elements of the third tab."))
                                ->end() // end TitledSection
                            ->endExample()
                        ->startExample("with plugin singleDirty", array(
                            "plugins" => array("singleDirty")
                        ))
                            ->startGroup("TitledSection", array("title" => "First tab (TitledSection)"))
                                ->input("Textbox", array("label" => "tab 1"))
                                ->end() // end TitledSection
                            ->startGroup("TitledSection", array("title" => "Second tab (TitledSection)"))
                                ->input("Checkbox", array("label" => "tab 2"))
                                ->end() // end TitledSection
                            ->startGroup("TitledSection", array("title" => "Third tab (TitledSection)"))
                                ->input("UpdateTextbox", array("label" => "tab 3"))
                                ->end() // end TitledSection
                            ->endExample()
                        ->endElementDocu()
                
                    // StepGroup  
                    ->startElementDocu("StepGroup")
                        ->startExample("default")
                            ->startGroup("TitledSection", array("title" => "First tab (TitledSection)"))
                                ->html("SimpleHtml", array("html" => "Here are the elements of the first tab."))
                                ->end() // end TitledSection
                            ->startGroup("TitledSection", array("title" => "Second tab (TitledSection)"))
                                ->html("SimpleHtml", array("html" => "Here are the elements of the second tab."))
                                ->end() // end TitledSection
                            ->startGroup("TitledSection", array("title" => "Third tab (TitledSection)"))
                                ->html("SimpleHtml", array("html" => "Here are the elements of the third tab."))
                                ->end() // end TitledSection
                            ->endExample()
                        ->endElementDocu()
                
                    // SelectGroup
                    ->startElementDocu("SelectGroup")
                        ->startExample("Basics")
                            ->startGroup("TitledSection", array("title" => "First tab (TitledSection)"))
                                ->html("SimpleHtml", array("html" => "Here are the elements of the first tab."))
                                ->end()
                            ->startGroup("TitledSection", array("title" => "Second tab (TitledSection)"))
                                ->html("SimpleHtml", array("html" => "Here are the elements of the second tab."))
                                ->end()
                            ->startGroup("TitledSection", array("title" => "Third tab (TitledSection)"))
                                ->html("SimpleHtml", array("html" => "Here are the elements of the third tab."))
                                ->end()
                            ->endExample()
                        ->startExample("with label", array("label" => "tabs"))
                            ->startGroup("TitledSection", array("title" => "First tab (TitledSection)"))
                                ->html("SimpleHtml", array("html" => "Here are the elements of the first tab."))
                                ->end()
                            ->startGroup("TitledSection", array("title" => "Second tab (TitledSection)"))
                                ->html("SimpleHtml", array("html" => "Here are the elements of the second tab."))
                                ->end()
                            ->startGroup("TitledSection", array("title" => "Third tab (TitledSection)"))
                                ->html("SimpleHtml", array("html" => "Here are the elements of the third tab."))
                                ->end()
                            ->endExample()
                        ->endElementDocu()
                
                    // LazyAddGroup
                    ->startElementDocu("LazyAddGroup")
                        ->startExample("default (with SelectGroup)")
                            ->input("Textbox", array("label" => "name"))
                            ->input("Textarea", array("label" => "text"))
                            ->endExample()
                        ->startExample("with TabGroup", array("group" => array("name" => "TabGroup")))
                            ->input("Textbox", array("label" => "name"))
                            ->input("Textarea", array("label" => "text"))
                            ->endExample()
                        ->startExample("with StepGroup", array("group" => array("name" => "StepGroup")))
                            ->input("Textbox", array("label" => "name"))
                            ->input("Textarea", array("label" => "text"))
                            ->endExample()
                        ->startExample("in single mode", array("mode" => "single", "label" => "code"))
                            ->input("Codearea", array("label" => "name"))
                            ->endExample()
                        ->endElementDocu()
                        
                    ->endTypeDocu()
                
                // HTML
                ->startTypeDocu("html")
                    
                    // SimpleHtml
                    ->startElementDocu("SimpleHtml")
                        ->example("Basics", array("html" => "This is <i>html</i> generated with <b>SimpleHtml</b>."))
                        ->endElementDocu()
                
                    ->startElementDocu("IFrame")
                        ->example("Basics", array("src" => "&preview", "height" => "500px"))
                        ->endElementDocu()
                
                    ->endTypeDocu()
                
                
                // TEXT
                ->startTypeDocu("text")
                    
                    // Headline
                    ->startElementDocu("Headline")
                        ->example("Basics", array("text" => "This is a headline generated with 'Headline'."))
                        ->endElementDocu()
                
                    ->endTypeDocu()
                
                // INPUT
                ->startTypeDocu("input")
                    
                    // Textbox
                    ->startElementDocu("Textbox")
                        ->example("without label")
                        ->example("bigger input", array("bigger" => true))
                        ->example("with label", array("label" => "Label of Textbox"))
                        ->endElementDocu()
                
                    // TranslationTextbox
                    ->startElementDocu("TranslationTextbox")
                        ->example("without label")
                        ->example("with label", array("label" => "Label of Textbox"))
                        ->endElementDocu()
                
                    // AutoCompleteTextbox
                    ->startElementDocu("AutoCompleteTextbox")
                        ->example("without label", array(
                            "data" => $autoCompleteData
                        ))
                        ->example("with label", array(
                            "label" => "Label of Textbox",
                            "data" => $autoCompleteData
                        ))
                        ->example("get data with event", array(
                            "label" => "Label of Textbox",
                            "data" => array(
                                "event" => "getExampleData",
                                "module" => "backend"
                            )
                        ))
                        ->endElementDocu()
                
                    // UpdateTextbox
                    ->startElementDocu("UpdateTextbox")
                        ->example("without label", array(
                            "value" => "test",
                            "validators" => array(
                                "notEmpty" => true
                            )
                        ))
                        ->example("bigger input", array(
                            "bigger" => true,
                            "label" => "Label of UpdateTextbox",
                            "value" => "test",
                            "validators" => array(
                                "notEmpty" => true
                            )
                        ))
                        ->example("with label", array(
                            "label" => "Label of UpdateTextbox",
                            "value" => "test",
                            "validators" => array(
                                "notEmpty" => true
                            )
                        ))
                        ->endElementDocu()
                
                    // AutoCompleteUpdateTextbox
                    ->startElementDocu("AutoCompleteUpdateTextbox")
                        ->example("without label", array(
                            "data" => $autoCompleteData
                        ))
                        ->example("with label", array(
                            "label" => "Label of Textbox",
                            "data" => $autoCompleteData
                        ))
                        ->endElementDocu()
                    
                    // Textarea
                    ->startElementDocu("Textarea")
                        ->example("without label")
                        ->example("bigger input", array("bigger" => true))
                        ->example("with label", array("label" => "Label of Textarea"))
                        ->endElementDocu()
                
                    ->startElementDocu("Codearea")
                        ->example("without label")
                        ->example("with label", array("label" => "Label of Codearea"))
                        ->endElementDocu() 
                    
                    // Slider
                    ->startElementDocu("Slider")
                        ->example("without label", array("min" => 10, "max" => 50, "value" => 20))
                        ->example("with label", array("label" => "Label of Slider"))
                        ->endElementDocu() 
                
                    // DatePicker
                    ->startElementDocu("DatePicker")
                        ->example("without label")
                        ->example("with label", array("label" => "Label of DatePicker"))
                        ->endElementDocu() 
                
                    // DatePicker
                    ->startElementDocu("ColorPicker")
                        ->example("without label")
                        ->example("with label", array("label" => "Label of ColorPicker"))
                        ->endElementDocu() 
                
                    // Editor
                    ->startElementDocu("Editor")
                        ->example("without label")
                        ->example("with label", array("label" => "Label of Editor"))
                        ->endElementDocu() 
                
                    // TranslationEditor
                    ->startElementDocu("TranslationEditor")
                        ->example("without label")
                        ->example("with label", array("label" => "Label of Editor"))
                        ->endElementDocu() 
                
                    // Fileuploader
                    ->startElementDocu("Fileuploader")
                        ->example("Basics")
                        ->endElementDocu() 
                
                    // SimpleImageUploader
                    ->startElementDocu("SimpleImageUploader")
                        ->example("without label")
                        ->example("with label", array("label" => "Label of SimpleImageUploader"))
                        ->endElementDocu() 
                
                    // Button
                    ->startElementDocu("Button")
                        ->example("Basics", array("value" => "download file"))
                        ->endElementDocu() 
                
                    // Switcher
                    ->startElementDocu("Switcher")
                        ->example("Basics", array("label" => "more than one content"))
                        ->endElementDocu() 
                
                    // RadioSwitcher
                    ->startElementDocu("RadioSwitcher")
                        ->example("Basics", array(
                            "label" => "accept content",
                            "data" => array(
                                array("id" => "text"),
                                array("id" => "image"),
                                array("id" => "video")
                            )
                        ))
                        ->example("buttons on the right", array(
                            "label" => "accept content",
                            "dataPosition" => "right",
                            "data" => array(
                                array("id" => "text"),
                                array("id" => "image")
                            )
                        ))
                        ->endElementDocu() 
                
                    // Checkbox
                    ->startElementDocu("Checkbox")
                        ->example("without label, with description", array("value" => true, "description" => "Description of Checkbox"))
                        ->example("with label", array("label" => "Label of Checkbox"))
                        ->endElementDocu() 
                
                    // Radiobutton
                    ->startElementDocu("Radiobutton")
                        ->example("without label", array(
                            "value" => true,
                            "data" => array(
                                array(
                                    "id"   => "1",
                                    "name" => "Home"
                                ),
                                array(
                                    "id"   => "2",
                                    "name" => "Imprint"
                                ),
                                array(
                                    "id"   => "3",
                                    "name" => "Contact"
                                )
                            )
                        ))
                        ->example("with label", array(
                            "label" => "Label of Radiobutton",
                            "data" => array(
                                array("id" => "Number 1"),
                                array("id" => "A long description, so you can see how it looks like, when there is so much text"),
                                array("id" => "Number 3")
                            )
                        ))
                        ->endElementDocu() 
                    
                    ->endTypeDocu()
                
                // LIST
                ->startTypeDocu("list")
                    
                    // Finder
                    ->startElementDocu("Finder")
                        ->example("default", array(
                            "data" => array(
                                array(
                                    "name" => "Pete Jackson",
                                    "age" => "47",
                                    "email" => "petejackson@myemail.com"
                                ),
                                array(
                                    "name" => "Jennifer Thomson",
                                    "age" => "29",
                                    "email" => "jennyintoronto@directweb.com"
                                )
                            )
                        ))
                        ->example("with label", array(
                            "label" => "Label of Finder",
                            "data" => array(
                                array(
                                    "name" => "Pete Jackson",
                                    "age" => "47",
                                    "email" => "petejackson@myemail.com"
                                ),
                                array(
                                    "name" => "Jennifer Thomson",
                                    "age" => "29",
                                    "email" => "jennyintoronto@directweb.com"
                                )
                            )
                        ))
                        ->example("recursive", array(
                            "recursive" => "parent",
                            "data" => array(
                                array(
                                    "id" => 1,
                                    "page" => "Home",
                                    "clicks" => 542,
                                    "parent" => null
                                ),
                                array(
                                    "id" => 2,
                                    "page" => "About",
                                    "clicks" => 322,
                                    "parent" => null
                                ),
                                array(
                                    "id" => 3,
                                    "page" => "Games",
                                    "clicks" => 1240,
                                    "parent" => 1
                                ),
                                array(
                                    "id" => 4,
                                    "page" => "Adventure Games",
                                    "clicks" => 304,
                                    "parent" => 3
                                ),
                                array(
                                    "id" => 5,
                                    "page" => "Sport Games",
                                    "clicks" => 122,
                                    "parent" => 3
                                ),
                                array(
                                    "id" => 6,
                                    "page" => "imprint",
                                    "clicks" => 21,
                                    "parent" => null
                                )
                            )
                        ))
                        ->example("recursive & draggable", array(
                            "recursive" => "parent",
                            "draggable" => true,
                            "data" => array(
                                array(
                                    "id" => 1,
                                    "page" => "Home",
                                    "clicks" => 542,
                                    "parent" => null
                                ),
                                array(
                                    "id" => 2,
                                    "page" => "About",
                                    "clicks" => 322,
                                    "parent" => null
                                ),
                                array(
                                    "id" => 3,
                                    "page" => "Games",
                                    "clicks" => 1240,
                                    "parent" => 1
                                ),
                                array(
                                    "id" => 4,
                                    "page" => "Adventure Games",
                                    "clicks" => 304,
                                    "parent" => 3
                                ),
                                array(
                                    "id" => 5,
                                    "page" => "Sport Games",
                                    "clicks" => 122,
                                    "parent" => 3
                                ),
                                array(
                                    "id" => 6,
                                    "page" => "imprint",
                                    "clicks" => 21,
                                    "parent" => null
                                )
                            )
                        ))
                        ->example("recursive, draggable & scrollable", array(
                            "recursive" => "parent",
                            "draggable" => true,
                            "data" => array(
                                array(
                                    "id" => 1,
                                    "page" => "Home",
                                    "parent" => null
                                ),
                                array(
                                    "id" => 2,
                                    "page" => "About",
                                    "parent" => null
                                ),
                                array(
                                    "id" => 3,
                                    "page" => "Games",
                                    "parent" => 1
                                ),
                                array(
                                    "id" => 4,
                                    "page" => "Adventure Games",
                                    "parent" => 3
                                ),
                                array(
                                    "id" => 5,
                                    "page" => "Sport Games",
                                    "parent" => 3
                                ),
                                array(
                                    "id" => 6,
                                    "page" => "sport",
                                    "parent" => null
                                ),
                                array(
                                    "id" => 7,
                                    "page" => "football",
                                    "parent" => 6
                                ),
                                array(
                                    "id" => 8,
                                    "page" => "tennis",
                                    "parent" => 6
                                ),
                                array(
                                    "id" => 9,
                                    "page" => "badminton",
                                    "parent" => 6
                                ),
                                array(
                                    "id" => 10,
                                    "page" => "media",
                                    "parent" => null
                                ),
                                array(
                                    "id" => 11,
                                    "page" => "photos",
                                    "parent" => 10
                                ),
                                array(
                                    "id" => 12,
                                    "page" => "videos",
                                    "parent" => 10
                                ),
                                array(
                                    "id" => 13,
                                    "page" => "websites",
                                    "parent" => 10
                                ),
                                array(
                                    "id" => 14,
                                    "page" => "friends",
                                    "parent" => null
                                ),
                                array(
                                    "id" => 15,
                                    "page" => "Jack Bauer",
                                    "parent" => 14
                                ),
                                array(
                                    "id" => 16,
                                    "page" => "about",
                                    "parent" => 15
                                ),
                                array(
                                    "id" => 17,
                                    "page" => "work",
                                    "parent" => 15
                                ),
                                array(
                                    "id" => 18,
                                    "page" => "Lusi Smith",
                                    "parent" => 14
                                ),
                                array(
                                    "id" => 19,
                                    "page" => "about",
                                    "parent" => 18
                                ),
                                array(
                                    "id" => 20,
                                    "page" => "work",
                                    "parent" => 18
                                ),
                                array(
                                    "id" => 21,
                                    "page" => "Ryan Howard",
                                    "parent" => 14
                                ),
                                array(
                                    "id" => 22,
                                    "page" => "about",
                                    "parent" => 21
                                ),
                                array(
                                    "id" => 23,
                                    "page" => "work",
                                    "parent" => 21
                                ),
                                array(
                                    "id" => 24,
                                    "page" => "contact",
                                    "parent" => null
                                ),
                                array(
                                    "id" => 25,
                                    "page" => "imprint",
                                    "parent" => null
                                )
                            )
                        ))
                        ->endElementDocu() 
                
                    // PagerFinder
                    ->startElementDocu("DataPager")
                        ->example("default (with SelectGroup)", array(
                            "data" => array(
                                "event" => "getPagerFinderData",
                                "module" => "backend"
                            )
                        ))
                        ->example("with TabGroup", array(
                            "group" => "TabGroup",
                            "data" => array(
                                "event" => "getPagerFinderData",
                                "module" => "backend"
                            )
                        ))
                        ->example("with StepGroup", array(
                            "group" => "StepGroup",
                            "data" => array(
                                "event" => "getPagerFinderData",
                                "module" => "backend"
                            )
                        ))
                        ->endElementDocu() 
                
                
                    // Selector
                    ->startElementDocu("Selector")
                        ->example("without label", array(
                            "data" => array(
                                array(
                                    "id" => "edit"
                                ),
                                array(
                                    "id" => "add"
                                )
                            )
                        ))
                        ->example("with label", array(
                            "label" => "label of Selector",
                            "translateValues" => false,
                            "data" => array(
                                array(
                                    "id" => 1,
                                    "column 1" => "value 1"
                                ),
                                array(
                                    "id" => 2,
                                    "column 1" => "value 2"
                                )
                            )
                        ))
                        ->endElementDocu()
                    
                    
                    ->startElementDocu("UpdateSelector")
                        ->example("without label", array(
                            "data" => array(
                                array(
                                    "id" => "edit"
                                ),
                                array(
                                    "id" => "add"
                                )
                            )
                        ))
                        ->example("with label", array(
                            "label" => "label of Selector",
                            "translateValues" => false,
                            "data" => array(
                                array(
                                    "id" => 1,
                                    "column 1" => "value 1"
                                ),
                                array(
                                    "id" => 2,
                                    "column 1" => "value 2"
                                )
                            )
                        ))
                        ->endElementDocu()
                
                    ->endTypeDocu();
        
        
        
        $this->framework
            ->startGroup("LazyMainTab", array("title" => "Styling"))
                ->startGroup("TitledGroup", array("title" => "inline styles", "open" => false))
                    ->html("SimpleHtml", $this->saveArray(array(
                        "html" => "This is a test <b>text</b>",
                        "style" => array(
                            "border" => "2px solid #00f",
                            "background" => "rgba(255, 0, 0, 0.2)",
                            "padding" => "10px"
                        )
                    )))
                    ->startGroup("TitledGroup", array("title" => "source", "open" => false))
                        ->text("Code", array("array" => $this->getLastArray()))
                        ->end() // end TitledGroup (source)
                    ->end() // end TitledGroup
        
                ->startGroup("TitledGroup", array("title" => "classes", "open" => false))
                    ->html("SimpleHtml", $this->saveArray(array(
                        "html" => "This is a test <b>text</b>",
                        "classes" => "myOwnClass"
                    )))
                    ->startGroup("TitledGroup", array("title" => "source", "open" => false))
                        ->text("Code", array("array" => $this->getLastArray()))
                        ->end() // end TitledGroup (source)
                    ->end(); // end TitledGroup
        
        $this->framework
            ->startGroup("LazyMainTab", array("title" => "Collections"))
                ->startGroup("TitledGroup", array("title" => "TestCollection"))
                    ->collection("TestCollection", array(0 => array("title" => "Collection with name")))
                    ->collection($this->module("backend")->testCollection, array(0 => array("title" => "Collection with array")));

    }
}

?>
