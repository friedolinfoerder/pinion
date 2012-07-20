<?php
/**
 * Module Template
 * 
 * PHP version 5.3
 * 
 * @author  Friedolin Förder <friedolin.foerder@pinion-cms.org>
 * @license MIT License
 * @link    http://www.pinion-cms.org/module/template
 */

namespace modules\template;

use \pinion\modules\Module;
use \pinion\modules\FrontendModule;
use \pinion\events\Event;
use \pinion\general\Registry;
use \pinion\files\DirectoryRearranger;

class Template extends Module {
    
    public function getResources() {
        return array_merge(parent::getResources(), array(
            "add template",
            "edit template",
            "delete template",
            "switch template",
            "switch templates of contents"
        ));
    }
    
    public function addListener() {
        parent::addListener();
        
        if($this->identity) {
            $this->addEventListener("getModuleTemplates");
            $this->addEventListener("validateTemplate");
        
            if($this->hasPermission("add template"))    $this->addEventListener("addTemplate");
            if($this->hasPermission("edit template")) {
                $this
                    ->addEventListener("getFile")
                    ->addEventListener("editCode")
                    ->addEventListener("edit");
            }
            if($this->hasPermission("switch templates of contents")) {
                $this
                    ->addEventListener("useModuleTemplate")
                    ->addEventListener("deleteModuleTemplate");
            }
        }
    }
    
    public function validateTemplate(Event $event) {
        $name = $event->getInfo("name");
        
        $valid = $this->_templateIsValid($name) ?: $this->translate("template %s already exists", "<b>".$name."</b>");
        $this->response->setInfo("valid", $valid);
    }
    
    protected function _templateIsValid($name) {
        return !file_exists(TEMPLATES_PATH.$name);
    }
    
    public function addTemplate(Event $event) {
        $name = $event->getInfo("name");
        
        if(!$this->_templateIsValid($name)) {
            return $this->response->addWarning($this, $this->translate("template %s was not created, because there is already a template with the same name", $name));
        }
        
        // make template directory
        mkdir(TEMPLATES_PATH.$name);
        
        $title = $event->getInfo("title");
        $author = $event->getInfo("author");
        $version = $event->getInfo("version");
        $description = $event->getInfo("description");
        $modules = $event->getInfo("modules");
        
        $iniContent = "";
        if($title) {
            $iniContent .= "title = '$title'\n";
        }
        if($author) {
            $iniContent .= "author = '$author'\n";
        }
        if($version) {
            $iniContent .= "version = '$version'\n";
        }
        if($description) {
            $iniContent .= "description = '$description'\n";
        }
        if(!empty($iniContent)) {
            file_put_contents(TEMPLATES_PATH.$name."/info.ini", $iniContent);
        }
        
        if($modules) {
            foreach($modules as $module) {
                mkdir(TEMPLATES_PATH.$name."/".$module["module"]);
                if(isset($module["templates"])) {
                    foreach($module["templates"] as $template) {
                        if(isset($template["template"])) {
                            mkdir(TEMPLATES_PATH.$name."/".$module["module"]."/".$template["template"]);
                            $code = isset($template["code"]) ? $template["code"] : "<div></div>";
                            file_put_contents(TEMPLATES_PATH.$name."/".$module["module"]."/".$template["template"]."/_main.php", $code);
                        }
                    }
                }
            }
        }
        
        $this->response->addWarning($this, $this->translate("%s %s added", $this->translate("template"), $name));
    }
    
    public function useModuleTemplate(Event $event) {
        $name = $event->getInfo("name");
        $module = $event->getInfo("module");
        $use = $event->getInfo("use");
        
        $pageData = $this->module("page")->data;
        $where = array(
            "module" => $module,
            "instance_id" => null
        );
        
        if($use == "page") {
            $where["content_id"] = $this->session->getParameter("pageContent");
        }
        
        // update content
        $pageData->massUpdate("content", array(
            "templatepath" => $name
        ), $where);
        
        // update content on page
        $contents = $pageData->find_all_by_module_and_content_id("content", $module, $this->session->getParameter("pageContent"));
        foreach($contents as $content) {
            $this->module("page")->dispatchEvent("content", array(
                "content" => $content
            ));
        }
    }
    
    public function deleteModuleTemplate(Event $event) {
        $name = $event->getInfo("name");
        $module = $event->getInfo("module");
        $use = $event->getInfo("delete");
        
        $pageData = $this->module("page")->data;
        $where = array(
            "module" => $module,
            "templatepath" => $name,
            "instance_id" => null
        );
        
        if($use == "page") {
            $where["content_id"] = $this->session->getParameter("pageContent");
        }
        
        // contents
        $contents = $pageData->find_all_by_module_and_content_id_and_templatepath("content", $module, $this->session->getParameter("pageContent"), $name);
        
        // update content
        $pageData->massUpdate("content", array(
            "templatepath" => null
        ), $where);
        
        // update content on page
        foreach($contents as $content) {
            $this->module("page")->dispatchEvent("content", array(
                "content" => $content
            ));
        }
    }
    
    public function edit(Event $event) {
        $templates = $event->getInfo("templates");
        
        foreach($templates as $template) {
            if(isset($template["deleted"]) && $template["deleted"] == true) {
                if($this->hasPermission("delete template")) {
                    DirectoryRearranger::remove(TEMPLATES_PATH.$template["id"]);
                    $this->response->addSuccess($this, $this->translate("%s %s deleted", $this->translate("template"), "<b>".$template["id"]."</b>"));
                } else {
                    $this->response->addWarning($this, $this->translate("The template %s was not deleted, because you don't have the permission", "<b>".$template["id"]."</b>"));
                }
            } else {
                if(isset($template["use"]) && $this->hasPermission("switch template")) {
                    Registry::setTemplate($template["id"]);
                    $this->response->addSuccess($this, $this->translate("template switched to %s", "<b>".$template["id"]."</b>"));
                }
            }
        }
    }
    
    
    public function editCode(Event $event) {
        $name = $event->getInfo("name");
        $module = $event->getInfo("module");
        $template = $event->getInfo("template");
        $code = $event->getInfo("code");
        
        // update file
        if(file_exists(TEMPLATES_PATH."$template/$module/$name/_main.php")) {
            file_put_contents(TEMPLATES_PATH."$template/$module/$name/_main.php", $code);
            
            // add success message
            $this->response->addSuccess($this, $this->translate("template file %s edited", "<b>".$name."</b>"));
        }
    }
    
    
    public function getFile(Event $event) {
        $name = $event->getInfo("name");
        $module = $event->getInfo("module");
        $template = $event->getInfo("template");
        
        if(file_exists(TEMPLATES_PATH."$template/$module/$name/_main.php")) {
            $this->framework
                ->key("elements")
                    ->input("Codearea", array(
                        "value" => file_get_contents(TEMPLATES_PATH."$template/$module/$name/_main.php"),
                        "infoKey" => "code",
                        "events" => array(
                            "event" => "editCode",
                            "info" => array(
                                "name" => $name,
                                "module" => $module,
                                "template" => $template
                            )
                        )
                    ));
        } else {
            $this->framework
                ->key("elements")
                    ->html("SimpleHtml", array("html" => "No file available"));
        }
    }
    
    
    public function getModuleTemplates(Event $event) {
        $name = $event->getInfo("name");
        
        // find module templates
        $templates = array();
        $templateDir = new \DirectoryIterator(TEMPLATES_PATH.$name);
        $moduleDirs = array();
        foreach($templateDir as $moduleFile) {
            if($moduleFile->isDir() && !$moduleFile->isDot()) {
                $moduleFile = $moduleFile->getFilename();
                if($moduleFile{0} != ".") {
                    $moduleTemplates = array(
                        "id" => $moduleFile,
                        "name" => $moduleFile,
                        "template" => $name,
                        "templates" => array()
                    );
                    // find module templates variations
                    $oneModuleTemplate = false;
                    $moduleDir = new \DirectoryIterator(TEMPLATES_PATH.$name."/".$moduleFile);
                    foreach($moduleDir as $variationFile) {
                        if($variationFile->isDir() && !$variationFile->isDot()) {
                            $variationFile = $variationFile->getFilename();
                            if($variationFile{0} != ".") {
                                if(is_file(TEMPLATES_PATH.$name."/".$moduleFile."/".$variationFile."/_main.php")) {
                                    $template = array(
                                        "id"   => $variationFile,
                                        "name" => $variationFile,
                                        "template" => $name,
                                        "module" => $moduleFile
                                    );
                                    if(is_file(TEMPLATES_PATH.$name."/".$moduleFile."/".$variationFile."/icon.png")) {
                                        $template["icon"] = TEMPLATES_URL."/".$name."/".$moduleFile."/".$variationFile."/icon.png";
                                    }
                                    $oneModuleTemplate = true;
                                    $moduleTemplates["templates"][] = $template;
                                }
                            }
                        }
                    }
                    if($oneModuleTemplate) {
                        $templates[] = $moduleTemplates;
                    }
                }
            }
        }
        
        $this->framework
            ->key("elements")
                ->list("Finder", array(
                    "scrollable" => false,
                    "renderer" => array(
                        "name" => "ModuleTemplateRenderer",
                        "events" => array(
                            "event" => "editModuleTemplate"
                        )
                    ),
                    "data" => $templates,
                    "groupEvents" => "modules"
                ));
    }
    
    public function defineBackend() {
        
        $availableTemplates = array();
        $currentTemplate = Registry::getTemplate();
        
        // find templates
        $templatesDir = new \DirectoryIterator(TEMPLATES_PATH);
        foreach($templatesDir as $file) {
            if($file->isDir() && !$file->isDot()) {
                $file = $file->getFilename();
                if($file{0} != ".") {
                    $icon = file_exists(TEMPLATES_PATH.$file."/icon.png") ? TEMPLATES_URL."/$file/icon.png" : MODULES_URL."/{$this->name}/defaultTemplateIcon.png";
                    $title = $file;
                    $author = null;
                    $version = "";
                    $description = null;
                    if(file_exists(TEMPLATES_PATH.$file."/info.ini")) {
                        $info = parse_ini_file(TEMPLATES_PATH.$file."/info.ini", true);
                        if(isset($info["title"])) {
                            $title = $this->translate($info["title"]);
                        }
                        if(isset($info["author"])) {
                            $author = $info["author"];
                        }
                        if(isset($info["version"])) {
                            $version = $info["version"];
                        }
                        if(isset($info["description"])) {
                            $description = $info["description"];
                        }
                    }
                    // add to templates
                    $availableTemplates[] = array(
                        "id" => $file,
                        "name" => $file,
                        "title" => $title,
                        "author" => $author,
                        "version" => $version,
                        "description" => $description,
                        "icon" => $icon,
                        "active" => ($file == $currentTemplate)
                    );
                    
                }
            }
        }
        
        // the active template should be at the first position
        $templateIndex = -1;
        foreach($availableTemplates as $index => $template) {
            if($currentTemplate == $template["id"]) {
                $templateIndex = $index;
                break;
            }
        }
        $template = array_splice($availableTemplates, $templateIndex, 1);
        array_unshift($availableTemplates, $template[0]);
        
        
        $this->framework
            ->startGroup("LazyMainTab", array("title" => "Templates"))
                ->list("Finder", array(
                    "scrollable" => false,
                    "renderer" => array(
                        "name" => "TemplateRenderer",
                        "events" => array(
                            "event" => "edit"
                        )
                    ),
                    "data" => $availableTemplates,
                    "groupEvents" => "templates"
                ));
        
        if($this->hasPermission("add template")) {
            $usableModules = self::$moduleManager->getUsableFrontendModules();
            $moduleData = array();
            foreach($usableModules as $name => $module) {
                $moduleData[] = array("id" => $name, "title" => $this->translate($module->information["title"]));
            }
            
            
            $this->framework
                ->startGroup("LazyMainTab", array("title" => "Add template", "groupEvents" => true, "validate" => "all"))
                    ->input("Textbox", array(
                        "label" => "name",
                        "validators" => array(
                            "notEmpty" => true,
                            "file" => true,
                            "events" => array(
                                "event" => "validateTemplate"
                            )
                        ),
                        "events" => array(
                            "event" => "addTemplate"
                        )
                    ))
                    ->input("Textbox", array(
                        "label" => "title",
                        "events" => array(
                            "event" => "addTemplate"
                        )
                    ))
                    ->input("Textbox", array(
                        "label" => "author",
                        "events" => array(
                            "event" => "addTemplate"
                        )
                    ))
                    ->input("Textbox", array(
                        "label" => "version",
                        "events" => array(
                            "event" => "addTemplate"
                        )
                    ))
                    ->input("Textarea", array(
                        "label" => "description",
                        "events" => array(
                            "event" => "addTemplate"
                        )
                    ))
                    ->startGroup("LazyAddGroup", array(
                        "label" => "add module template",
                        "group" => array(
                            "groupEvents" => "modules"
                        )
                    ))
                        ->list("Selector", array(
                            "label" => "module",
                            "data" => $moduleData,
                            "validators" => array(
                                "notEmpty" => true
                            ),
                            "events" => array(
                                "event" => "addTemplate"
                            )
                        ))
                        ->startGroup("LazyAddGroup", array(
                            "label" => "add template",
                            "group" => array(
                                "name" => "TabGroup",
                                "groupEvents" => "templates"
                            )
                        ))
                            ->input("Textbox", array(
                                "label" => "template",
                                "data" => $moduleData,
                                "validators" => array(
                                    "notEmpty" => true
                                ),
                                "events" => array(
                                    "event" => "addTemplate"
                                )
                            ))
                            ->input("Codearea", array(
                                "label" => "code",
                                "value" => "<div></div>",
                                "validators" => array(
                                    "notEmpty" => true
                                ),
                                "events" => array(
                                    "event" => "addTemplate"
                                )
                            ));
        }
        
                
            
    }
    
    
}


?>