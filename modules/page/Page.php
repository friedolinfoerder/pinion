<?php
/**
 * Module Page
 * 
 * PHP version 5.3
 * 
 * @author  Friedolin Förder <friedolin.foerder@pinion-cms.org>
 * @license MIT License
 * @link    http://www.pinion-cms.org/module/page
 */

namespace modules\page;

use \pinion\modules\FrontendModule;
use \pinion\modules\Module;
use \pinion\general\Response;
use \pinion\events\Event;
use \pinion\general\TemplateBuilder;
use \pinion\general\TemplatePostProcessor;
use \pinion\data\database\ActiveRecord;
use \pinion\general\Registry;
use \pinion\data\models\Page_content;
use \pinion\util\Object;

class Page extends FrontendModule {
    
    protected $areas = array();
    protected $_canAddModule = false;
    public $url;
    protected $page;
    protected $contents;
    public $_currentContentId = null;
    protected $_renderedContents = array();
    
    protected $newContentMapping = array();
    protected $moduleContentMapping = array();
    
    public function install() {
        $this->data
            ->createDataStorage("page", array(
                "url"   => array("type" => "varchar", "length" => 250, "translatable" => false),
                "title" => array("type" => "varchar", "length" => 100, "isNull" => true),
                "vars"  => array("type" => "text", "translatable" => false)
            ))
            ->createDataStorage("content", array(
                "module"        => array("type" => "varchar", "length" => 100, "translatable" => false),
                "moduleid"      => array("type" => "int", "isNull" => true),
                "position"      => array("type" => "int"),
                "visible"       => array("type" => "boolean"),
                "templatePath"  => array("type" => "varchar", "length" => 100, "isNull" => true, "translatable" => false),
                "areaName"      => array("type" => "varchar", "length" => 100, "isNull" => true, "translatable" => false),
                "content"
            ))
            ->createDataStorage("visit", array(
                "page"
            ), array(
                "revisions" => false
            ));
    }
    
    public function init() {
        $modules = self::$moduleManager->getUsableFrontendModules();
        foreach($modules as $module) {
            if($module->hasPermission("add")) {
                $this->_canAddModule = true;
                break;
            }
        }
    }
    
    public function addListener() {
        parent::addListener();
        
        if($this->identity) {
            $this->addEventListener("content");
            $this->addEventListener("getUrlData");
            $this->addEventListener("getStyles");
            $this->addEventListener("getContentsByType");
            $this->addEventListener("getContentsOfElement");
            $this->addEventListener("getContentsOfPage");
            $this->addEventListener("getContentElementsByType");
            $this->addEventListener("validateUrl");
            
            $this->addEventListener("addContent");
            $this->addEventListener("editContent");
            $this->addEventListener("editElement");
            $this->addEventListener("deleteContent");
            $this->addEventListener("revisions");
            
            $this->addEventListener("changeContent");
            $this->addEventListener("changeRevision");
            $this->addEventListener("changeVisibility");
            $this->addEventListener("changeBelonging");
            $this->addEventListener("changeStyle");
            
            $this->addEventListener("getEditModule");
            $this->addEventListener("editPage");
            
            $this->addEventListener("newVariablesEditor");
            
            if($this->hasPermission("sort contents"))                   $this->addEventListener("sort");                                                                    
            if($this->hasPermission("add page"))                        $this->addEventListener("addPage");  
            $this->addEventListener("deletePage");
        }
        
    }
    
    public function changeBelonging(Event $event) {
        $contents = $event->getInfo("contents");
        
        foreach($contents as $id) {
            $content = $this->data->find("content", $id);
            $module = $this->module($content->module);
            if($module && ($module->hasPermission("change assignment") || ($module->hasPermission("change assignment of own") && $content->isOwn()))) {
                $title = $this->translate($module->information["title"]);
                if(is_null($content->content_id)) {
                    $content->content_id = $this->session->getParameter("pageContent");
                    $this->response->addSuccess($this, $this->translate("The %s (%d) is now assigned to the page and so it only exists on this page", "<b>$title</b>", $id));
                } else {
                    $content->content_id = null;
                    $this->response->addSuccess($this, $this->translate("The %s (%d) is now assigned to the area %s and so it exists in this area on every page", "<b>$title</b>", $id, "<b>{$content->areaname}</b>"));
                }
                $content->save();
            }
        }
    }
    
    public function validateUrl(Event $event) {
        $url = $event->getInfo("url");
        $valid = $this->_urlIsValid($url) ?: $this->translate("page %s already exists", "<b>$url</b>");
        $this->response->setInfo("valid", $valid);
    }
    
    protected function _urlIsValid($url) {
        return is_null($this->data->find_by_url("page", $url));
    }
    
    public function page404() {
        $type = ($this->identity && $this->hasPermission("add page")) ? "add" : "404";
        $pageRenderer = new PageCreator($this->request->getGetParameter("page"), $type);
        $this->response->write($pageRenderer);
    }
    
    public function pageMaintenance() {
        $type = "maintenance";
        $pageRenderer = new PageCreator($this->request->getGetParameter("page"), $type);
        $this->response->write($pageRenderer);
    }
    
    public function edit(Event $event) {
        $id = $event->getInfo("id");
        $page = $this->data->find("page", $id);
       
        $url = $event->getInfo("url");
        $title = $event->getInfo("title");
        $vars = $event->getInfo("variables", array());
        $newVars = $event->getInfo("newVariables", array());
        if(!empty($vars) || !empty($newVars)) {
            $existingVars = json_decode($page->vars, true);
            foreach($newVars as $var) {
                if(isset($var["key"])) {
                    $existingVars[$var["key"]] = array_merge(array(
                        "value" => "",
                        "editor" => "input.Textbox"
                    ), $var);
                }
            }
            foreach($vars as $var) {
                if(isset($var["key"])) {
                    if(isset($var["delete"])) {
                        unset($existingVars[$var["key"]]);
                    } else {
                        $existingVars[$var["key"]]["value"] = $var["value"];
                    }
                }
            }
            $page->vars = json_encode($existingVars);
        }
        if($url && $this->_urlIsValid($url)) {
            $page->url = $url;
        }
        if(!is_null($title)) {
            if(is_string($title)) {
                $title = trim($title) == "" ? null : $title;
            }
            $page->title = $title;
        }
        $page->save();
        $this->response->addSuccess($this, $this->translate("page %s edited", "<b>".$url."</b>"));
    }
    
    public function editPage(Event $event) {
        $pages = $event->getInfo("pages");
        
        foreach($pages as $page) {
            $id = $page["id"];
            if(isset($page["deleted"]) && $page["deleted"] == true) {
                $this->_deletePage($id);
            } else {
                $this->dispatchEvent("edit", $page);
            }
        }
    }
    
    public function deletePage(Event $event) {
        $this->_deletePage($event->getInfo("id"));
    }
    
    protected function _deletePage($id) {
        $allowed = false;
        if($this->hasPermission("delete")) {
            $allowed = true;
        } else {
            $data = $this->data->find("page", $id);
            if($this->hasPermission("delete own") && $data->isOwn()) {
                $allowed = true;
            }
        }
        if($allowed) {
            $this->data->deleteData($id);
            $pageData = $this->data->find("page", $id);
            $this->response->addSuccess($this, $this->translate("page %s deleted", "<b>".$pageData->url."</b>"));
        }
    }
    
    public function getEditModule(Event $event) {
        
        $id = $event->getInfo("id");
        $moduleName = $event->getInfo("module");
        
        $module = $this->module($moduleName);
        $data = $module->data->find_by_id($moduleName, $id);
        if(is_null($data)) {
            $this->framework
                ->key("elements")
                    ->html("SimpleHtml", array("html" => "The element is not available"));
        } else {
            $this->framework
                ->key("elements")
                    ->module(ucfirst($moduleName), array(
                        "moduleId" => $data->id,
                        "contentId" => null,
                        "moduleName" => ucfirst($moduleName),
                        "name" => $moduleName,
                        "data" => $data->attributes(),
                        "content" => new \stdClass(),
                        "vars" => $module->setFrontendVars($data),
                        "_isNew" => false
                    ));
        }
        
    }
    
    public function getContentsOfElement(Event $event) {
        
        $id = $event->getInfo("id");
        $module = $event->getInfo("module");
        
        $data = $this->data->find_all_by_module_and_moduleid("content", $module, $id);
        
        if(empty($data)) {
            $this->framework
                ->key("elements")
                    ->html("SimpleHtml", array("html" => "no contents"));
        } else {
            $_this = $this;
            
            $this->framework
                ->key("elements")
                    ->list("Finder", array(
                        "data" => $this->data->getAttributes($data, array(
                            "*",
                            "page" => function($ar) use ($_this) {
                                $url = $_this->module("page")->data->find("page", $ar->parent->moduleid)->url;
                                return $url;
                            }
                        )),
                        "renderer" => array(
                            "name" => "SimpleContentRenderer",
                            "events" => array(
                                "event" => "editContent"
                            ) 
                        ),
                        "groupEvents" => "contents"   
                    ));
        }
    }
    
    public function getContentsOfPage(Event $event) {
        
        $id = $event->getInfo("id");
        
        $start = $event->getInfo("start");
        $end   = $event->getInfo("end");
        
        $pageContent = $this->data->find_by_module_and_moduleid("content", "page", $id);
        $options = array("conditions" => array("content_id = ?", $pageContent->id), "offset" => $start, "limit" => $end - $start);
        
        $contents = $this->data->all("content", $options);
        $data = array();
        foreach($contents as $content) {
            $module = $this->module($content->module);
            $moduleRow = $module->data->find($module->name, $content->moduleid);
            $data[] = array(
                "data" => $moduleRow->attributes(),
                "preview" => $module->preview($moduleRow),
                "content" => $content->attributes()
            );
        }
        
        $this->response->setInfo("data", $data);
        
        if($start == 0) {
            $this->response->setInfo("dataLength", $this->data->count("content", array("conditions" => array("content_id = ?", $pageContent->id))));
        }
    }
    
    /**
     * Event function for adding a page
     * 
     * @param Event $event 
     */
    public function addPage(Event $event) {
        $vars = $event->getInfo("variables", array());
        foreach($vars as $var) {
            
        }
        
        $url = $event->getInfo("url");
        if(is_null($url)) {
            return $this->response->addError($this, $this->translate("The page was not created, because no url is given"));
        }
        $title = $event->getInfo("title");
        
        $data = $this->data->find_by_url("page", $url);
        if(is_null($data)) {
            $page = $this->data->create("page", array(
                "url" => $url,
                "title" => $title,
                "vars" => json_encode($vars)
            ));
            $this->data->create("content", array(
                "module"   => "page",
                "moduleid" => $page->id,
                "position" => 0,
                "visible"  => true
            ));
            $this->response->addSuccess($this, $this->translate("page %s added", "<b>$url</b>"));
            
            // if the url is the same as the current url, restart the page
            if($this->request->getGetParameter("page") == $url) {
                $this->response->setInfo("restart", true);
            }
        } else {
            $this->response->addWarning($this, $this->translate("The page was not created, because there is already a page with the url %s", "<b>$url</b>"));
        }
    }
    
    /**
     * Event function for editing a content
     * 
     * @param Event $event 
     */
    public function editContent(Event $event) {
        $contents = $event->getInfo("contents");
        
        foreach($contents as $data) {
            if(!isset($data["id"])) {
                continue;
            }
            $id = $data["id"];
            $content = $this->data->find_by_id("content", $id);
            if(is_object($content)) {
                $module = $this->module($content->module);
                if($module) {
                    if(isset($data["deleted"]) && $data["deleted"] == true) {
                        if($module->hasPermission("delete") || ($module->hasPermission("delete own") && $content->isOwn())) {
                            $content->delete();
                            $this->response->addInfo("content.{$content->id}.remove", true);
                            $this->response->addSuccess($this, $this->translate("%s (%d) deleted", "<b>".$this->translate($module->information["title"])."</b>", $content->moduleid));
                        }
                    } elseif(isset($data["visible"])) {
                        if($module->hasPermission("change visibility") || ($module->hasPermission("change visibility of own") && $content->isOwn())) {
                            $content->update_attributes($data);
                            $this->response->addSuccess($this, $this->translate("%s (%d) edited", "<b>".$this->translate($module->information["title"])."</b>", $content->moduleid));
                        }
                    }
                } else {
                    $this->response->addWarning($this, $this->translate("The content was not edited, because the module %s is not usable", $content->module));
                }
            }
        }
        
    }
    
    public function editElement(Event $event) {
        $elements = $event->getInfo("elements");
        
        foreach($elements as $data) {
            $moduleString = $data["module"];
            $id = $data["id"];
            
            $module = $this->module($moduleString);
            if($module) {
                $moduleData = $module->data->find($moduleString, $id);
                if(isset($data["deleted"]) && $data["deleted"] == true) {
                    if($module->hasPermission("delete") || ($module->hasPermission("delete own") && $content->isOwn())) {
                        $moduleData->delete();
                        $contents = $this->data->find_all_by_module_and_moduleid("content", $moduleString, $id);
                        foreach($contents as $content) {
                            $content->delete();
                            $this->response->addInfo("content.{$content->id}.remove", true);
                        }
                        $this->response->addSuccess($this, $this->translate("%s (%d) deleted", "<b>".$this->translate($module->information["title"])."</b>", $content->moduleid));
                    } else {
                        $this->response->addWarning($this, $this->translate("The module %s with id %s was not deleted, because you don't have the permission.", "<b>".$this->translate($module->information["title"])."</b>", "<b>".$moduleData->id."</b>"));
                    }
                }
            }
        }
    }
    
    /**
     * Event function for getting the content instances of a specific type
     * 
     * @param Event $event
     * @return type 
     */
    public function getContentsByType(Event $event) {
        
        $start = $event->getInfo("start");
        $end   = $event->getInfo("end");
        
        $module = $event->getInfo("module");
        
        $options = array("conditions" => array("module = ?", $module), "offset" => $start, "limit" => $end - $start);
        if($event->hasInfo("visible")) {
            $options["conditions"][0] .= " AND visible = ?";
            $options["conditions"][] = $event->getInfo("visible");
        } elseif($event->hasInfo("deleted")) {
            $options["conditions"][0] .= " AND deleted = ?";
            $options["conditions"][] = $event->getInfo("deleted");
        }
        
        $contents = $this->data->all("content", $module, $options);
        $data = $this->data->getAttributes($contents, array(
            "*",
            "deleted" => function() {
                return 0;
            },
            "user" => function($ar) {
                if($ar) {
                    return $ar->username;
                }
            }
        ));
        $this->response->setInfo("data", $data);
        
        if($start == 0) {
            $options = array("conditions" => array("module = ?", $module));
            if($event->hasInfo("visible")) {
                $options["conditions"][0] .= " AND visible = ?";
                $options["conditions"][] = $event->getInfo("visible");
            } elseif($event->hasInfo("deleted")) {
                $options["conditions"][0] .= " AND deleted = ?";
                $options["conditions"][] = $event->getInfo("deleted");
            }
            $this->response->setInfo("dataLength", $this->data->count("content", $options));
        }
    }
    
    /**
     * Event function for getting the content elements of a specific type
     * 
     * @param Event $event 
     */
    public function getContentElementsByType(Event $event) {
        $module = $event->getInfo("module");
        $this->module($module)->getContents($event);
        
        $data = $this->response->getInfo("data");
        
        foreach($data as &$d) {
            $d["module"] = $module;
        }
        $this->response->setInfo("data", $data);
    }
    
    /**
     * Event function for getting the styles of a module
     * 
     * @param Event $event 
     */
    public function getStyles(Event $event) {
        $module = $event->getInfo("module");
        
        $templates = array(array(
            "id" => "standard",
            "name" => "standard"
        ));
        
        $path = TEMPLATES_PATH.Registry::getTemplate()."/".$module;
        if(is_dir($path)) {
            $dirs = new \DirectoryIterator($path);
            foreach($dirs as $dir) {
                $filename = $dir->getFilename();
                if($filename == "standard") continue;
                
                if($dir->isDir() && !$dir->isDot() && $filename{0} != ".") {
                    $template = array(
                        "id" => $filename,
                        "name" => $filename
                    );
                    if(is_file($path."/".$filename."/icon.png")) {
                        $template["icon"] = TEMPLATES_URL."/".Registry::getTemplate()."/".$module."/".$filename."/icon.png";
                    }
                    $templates[] = $template;
                }
            }
        }
        
        $this->response->setInfo("data", $templates);
    }
    
    /**
     * Event function for getting all urls
     * 
     * @param Event $event 
     */
    public function getUrlData(Event $event) {
        $pages = $this->data->all("page");
        $urlData = array();
        foreach($pages as $page) {
            $urlData[] = $page->url;
        }
        
        $this->response->setInfo("data", $urlData);
    }
    
    /**
     * The function for defining the resources of the module
     * 
     * @return array An array with all resources the module 
     */
    public function getResources() {
        
        $resources = parent::getResources();
        
        // a page can't be added anywhere in the page:
        // remove "add" from resources
        unset(
            $resources[array_search("add", $resources)],
            $resources[array_search("add existing", $resources)],
            $resources[array_search("add existing of own", $resources)],
            $resources[array_search("change assignment", $resources)],
            $resources[array_search("change assignment of own", $resources)]
        );
        
        return array_merge($resources, array(
            "sort contents",
            "add page"
        ));
    }
    
    /**
     * Event function for changing the style of an content
     * 
     * @param Event $event 
     */
    public function changeStyle(Event $event) {
        $id = $event->getInfo("id");
        $template = $event->getInfo("template");
        
        $content = $this->data->find("content", $id);
        $module = $this->module($content->module);
        if($module && ($module->hasPermission("change style") || ($module->hasPermission("change style of own") && $content->isOwn()))) {
            $content->templatepath = $template;
            $content->save();

            if($module->name == "page") {
                $this->response
                    ->addSuccess($this, $this->translate("Style of the page updated. A page refresh is necessary."))
                    ->setInfo("restart", true);
            } else {
                $this->dispatchEvent("content", array(
                    "content" => $content
                ));
            }
        }
        
    }
    
    /**
     * Event function for revert a content
     * 
     * @param Event $event 
     */
    public function changeRevision(Event $event) {
        
        $id = $event->getInfo("id");
        $module = $event->getInfo("module");
        $module = $this->module($module);
        $revision = $event->getInfo("revision");
        
        if($module) {
            $data = $module->data->find($module->name, $id);
            if($module->hasPermission("edit") || ($module->hasPermission("edit own") && $data->isOwn())) {
                $module->revert($data, $revision);
                
                // update contents
                $this->content($event);
            }
        }
    }
    
    /**
     * Event function for getting all available revisions of a module
     * 
     * @param Event $event 
     */
    public function revisions(Event $event) {
        $id = $event->getInfo("id");
        $module = $event->getInfo("module");
        
        $firstRevision = $this->module($module)->data->first($module, array("conditions" => array("instance_id = ?", $id)));
        if(is_object($firstRevision)) {
            $revisions = \pinion\data\models\Pinion_setting::all(array("conditions" => array("instance_id IS NOT NULL AND revision >= ?", $firstRevision->revision)));
        } else {
            $revisions = array();
        }
        
        $this->response->setInfo("revisions", $this->data->getAttributes($revisions));
    }
    
    /**
     * Event function for changing the visibility of a content
     * 
     * @param Event $event 
     */
    public function changeVisibility(Event $event) {
        $contents = $event->getInfo("contents");
        
        foreach($contents as $id => $value) {
            $content = $this->data->find("content", $id);
            $module = $this->module($content->module);
            if($module && ($module->hasPermission("change visibility") || ($module->hasPermission("change visibility of own") && $content->isOwn()))) {
                $content->visible = $value;
                $content->save();
                
                $visible = $value ? "visible" : "invisible";
                
                $this->response->addSuccess($this, $this->translate("%s (%d) is now %s", $this->translate($module->information["title"]), $id, $visible));
            }
        }
    }
    
    /**
     * Event function for deleting a content
     * 
     * @param Event $event 
     */
    public function deleteContent(Event $event) {
        $contents = $event->getInfo("contents");
        
        foreach($contents as $id) {
            $content = $this->data->find("content", $id);
            $module = $this->module($content->module);
            if($module) {
                if($module->hasPermission("delete") || ($module->hasPermission("delete own") && $content->isOwn())) {
                    $content->delete();
                
                    $this->response
                        ->setInfo("content.$id.remove", true)
                        ->addSuccess($this, $this->translate("%s (%d) deleted", $this->translate($module->information["title"]), $id));
                } else {
                    $this->response->addWarning($this, $this->translate("The content of the module %s was not deleted, because you don't have the permission", $content->module));
                }
            } else {
                $this->response->addWarning($this, $this->translate("The content was not deleted, because the module %s is not usable", $content->module));
            }
        }
    }
    
    /**
     * Event function for rendering the html of a content
     * 
     * @param Event $event
     */
    public function content(Event $event) {
        $sourceId = $event->getInfo("contentId");
        
        if($event->hasInfo("data")) {
            $data = $event->getInfo("data");
            
            // get contents
            $modulename = $data->module()->name;
            $moduleid = $data->id;
        } elseif($event->hasInfo("module") && $event->hasInfo("id")) {
            $data = $this->module($event->getInfo("module"))->data->find($event->getInfo("module"), $event->getInfo("id"));
            
            // get contents
            $modulename = $event->getInfo("module");
            $moduleid = $event->getInfo("id");
        } else {
            if($event->hasInfo("content")) {
                $content = $event->getInfo("content");
                $sourceId = $content->id;
            } else {
                $sourceId = $event->getInfo("id");
                $content = $this->data->find("content", $sourceId);
            }
            $data = $this->module($content->module)->data->find($content->module, $content->moduleid);
            
            $modulename = $content->module;
            $moduleid = $content->moduleid;
        }
        
        if($modulename == "page") return;
        // get contents with same module instance
        $contents = $this->data->all("content", array("conditions" => array("module = ? AND moduleid = ? AND (content_id IS NULL OR content_id = ?)", $modulename, $moduleid, $this->session->getParameter("pageContent"))));
        
        foreach($contents as $content) {
            $id = $content->id;
            
            // add event listener, but only if there is not this event listener
            $response = $this->response;
        
            if(is_null($this->_currentContentId)) {
                $self = $this;

                $this->module($content->module)->addEventListener("renderTemplate", function($event) use ($self, $response) {
                    $id = $self->_currentContentId;
                    if($id == $event->getInfo("id")) {
                        // set response information (vars)
                        $response->setInfo("content.$id.vars", $event->getInfo("vars"));
                    }
                });
            }
            $this->_currentContentId = $id;


            $html = $this->response->write($content)->flush();

            // set identifier if there is one given
            if($id == $sourceId && $event->hasInfo("identifier")) {
                $this->response->setInfo("content.$id.identifier", $event->getInfo("identifier"));
            }
            
            // set module id if there is one given
            if($id == $sourceId && $event->hasInfo("moduleid")) {
                $content->moduleid = $event->getInfo("moduleid");
            }
            
            // set response information (html, content, data)
            $this->response
                ->setInfo("content.$id.html", $html)
                ->setInfo("content.$id.content", $content->attributes())
                ->setInfo("content.$id.data", $data->attributes())
                ->addSuccess($this, $this->translate("the view of the page was updated"));
        }
        
        // dispatch, that all contents with this data has been updated
        $this->dispatchEvent("changeData", array(
            "data" => $data
        ));
        
    }

    public function addTemplate($title, $name) {
        $template = new \Template();
        $template->title = $title;
        $template->name = $name;
        $template->save();

        return (int)$template->id;
    }
    
    public function removeOneContent(Event $e) {
        $id = $e->getInfo("contentid");
        
        $content = $this->data->find("content", $id);
        $order = $content->position;
        $pageid = $content->page_id;
        $areaname = $content->areaname;
        $content->delete();
        
        // new sorting
        $sql = sprintf("UPDATE contents 
                        SET position = position-1 
                        WHERE `page_id` = $pageid AND `areaname` = '$areaname' AND position > %d 
                        ORDER BY position ASC", $order);
        $this->db->exec($sql);
        
        $this->response->addSuccess($this, "The content element was successfully deleted.");
    }
    
    public function sort(Event $event) {
        
        $areas = $event->getInfo("areas");
        
        foreach($areas as $areaname => $area) {
            foreach($area as $position => $id) {
                if(is_numeric($id)) {
                    $content = $this->data->find("content", $id);
                    $content->areaname = $areaname;
                    $content->position = $position;
                    $content->save();
                } elseif(isset($this->newContentMapping[$id])) {
                    if(isset($this->newContentMapping[$id]["id"])) {
                        $content = $this->data->find("content", $this->newContentMapping[$id]["id"]);
                        $content->areaname = $areaname;
                        $content->position = $position;
                        $content->save();
                    } else {
                        $this->newContentMapping[$id]["areaname"] = $areaname;
                        $this->newContentMapping[$id]["position"] = $position;
                    }  
                } else {
                    $this->newContentMapping[$id] = array(
                        "areaname" => $areaname,
                        "position" => $position
                    );
                }
            }
        }
        
        $this->response->addSuccess($this, "The positions of the contents were updated.");
    }
    
    public function setModuleContentMapping(Event $event, $moduleContent) {
        $identifier = $event->getInfo("identifier");
        $module = $event->getInfo("sender")->name;
        $this->moduleContentMapping[$identifier] = array("data" => $moduleContent, "module" => $module);
        $this->dispatchEvent("contentMapping", array(
            "content" => $moduleContent,
            "module" => $module,
            "identifier" => $identifier
        ));
    }
    
    public function getModuleContent($identifier) {
        return $this->moduleContentMapping["identifier"];
    }

    /**
     * Event function for adding a content to a page
     * 
     * @param Event $event
     * @return Page_content $content
     */
    public function addContent(Event $event) {
        
        $identifier = $event->getInfo("identifier");
        $areaname = $event->getInfo("areaname");
        
        $data = $this->moduleContentMapping[$identifier]["data"];
        $moduleId = $data->id;
        $modulename = $this->moduleContentMapping[$identifier]["module"];
        
        $position = 0;
        
        if(isset($this->newContentMapping[$identifier])) {
            $position = $this->newContentMapping[$identifier]["position"];
            $areaname = $this->newContentMapping[$identifier]["areaname"];
        }
        
        // create content
        $content = $this->data->create("content", array(
            "module"      => $modulename,
            "moduleid"    => $moduleId,
            "content_id"  => $this->session->getParameter("pageContent"),
            "areaname"    => $areaname,
            "position"    => $position,
            "visible"     => false
        ));
        
        if($identifier) {
            $this->newContentMapping[$identifier] = array("id" => $content->id);
        }
        
        $this->dispatchEvent("content", array(
            "contentId"  => $content->id,
            "data"       => $data,
            "identifier" => $identifier
        ));
        
        $this->response->addSuccess($this, $this->translate("%s added to this page", $this->module($modulename)->information["title"]));
        
        return $content;
    }
    
    /**
     * Event function for changing the existing content to another content
     * 
     * @param Event $event
     * @return type 
     */
    public function changeContent(Event $event) {
        
        $oldId = $event->getInfo("oldId");
        $identifier = $event->getInfo("identifier");
        $id = $event->getInfo("id");
        $module = $this->module($event->getInfo("module"));
        if($module) {
            // if the old id is already the id, do nothing and return
            if($oldId == $id) return;

            if($oldId) {
                $oldContent = $this->data->find_by_module_and_moduleid("content", $module->name, $oldId);

                $allowed = false;
                if($module->hasPermission("add existing")) {
                    $allowed = true;
                } else {
                    $newContent = $this->data->find_by_module_and_moduleid("content", $module->name, $id);
                    if($module->hasPermission("add existing of own") && $newContent->isOwn()) {
                        $allowed = true;
                    }
                }

                if($allowed) {
                    // change module id
                    $oldContent->moduleid = $id;

                    // save content
                    $oldContent->save();

                    $this->dispatchEvent("content", array(
                        "content" => $oldContent,
                        "identifier" => $identifier
                    ));
                }

            } else {
                $newContent = $module->data->find($module->name, $id);
                
                if($module->hasPermission("add existing")) {
                    $allowed = true;
                } else {
                    if($module->hasPermission("add existing of own") && $newContent->isOwn()) {
                        $allowed = true;
                    }
                }
                
                if($allowed) {
                    $this->setModuleContentMapping(new Event("add", array(
                        "sender"     => $module,
                        "identifier" => $identifier
                    )), $newContent);

                    // dispatch add content event
                    $this->dispatchEvent("addContent", array(
                        "identifier" => $identifier,
                        "areaname"   => $event->getInfo("areaname")
                    ));
                }
                
            }

            $this->response->addSuccess($this, $this->translate("%s (%d) edited", $this->translate($module->information["title"]), $event->getInfo("id")));
        }
    }

    public function setFrontendVars($data) {
        // reset template variables
        $this->areas = array();
        
        $_this = $this;
        $title = $data->title;
        
        $vars = Object::merge($this->getTemplateVariables(), json_decode($data->vars, true));
        $v = new \stdClass();
        foreach($vars as $key => $var) {
            $v->{$key} = $var["value"];
        }
        
        return array(
            "created" => $data->created,
            "updated" => $data->updated,
            "url" => $data->url,
            "title" => $title,
            "title_translated" => "$title",
            "vars" => $v,
            "area" => function($templateBuilder, $areaname) use($_this) {
                $_this->area($templateBuilder, $areaname);
            }
        );
    }
    
    protected function getTemplateVariables() {
        $variables = array();
        $pageContent = $this->response->getPageContent() ?: $this->data->find("content", $this->session->getParameter("pageContent"));
        $iniPaths = array(
            TEMPLATES_PATH.Registry::getTemplate()."/info.ini",
            TEMPLATES_PATH.Registry::getTemplate()."/page/info.ini",
            TEMPLATES_PATH.Registry::getTemplate()."/page/".($pageContent->templatepath ?: "standard")."/info.ini"
        );
        foreach($iniPaths as $iniPath) {
            if(file_exists($iniPath)) {
                $iniContent = parse_ini_file($iniPath, true);
                if(isset($iniContent["variables"]) && is_array($iniContent["variables"])) {
                    foreach($iniContent["variables"] as $key => $value) {
                        if(is_string($value)) {
                            if(isset($variables[$key])) {
                                $variables[$key]["value"] = $value;
                            } else {
                                $variables[$key] = array(
                                    "value" => $value,
                                    "editor" => "input.Textbox"
                                );
                            }
                        } elseif(is_array($value)) {
                            if(isset($variables[$key])) {
                                $variables[$key] = array_merge($variables[$key], $value);
                            } else {
                                $variables[$key] = array_merge(array(
                                    "value" => "",
                                    "editor" => "input.Textbox"
                                ), $value);
                            }
                        }
                    }
                }
            }
        }
        
        return $variables;
    }
    
    /**
     * Template Render Function: Prints out the html of the area
     * @param string The name of the area
     * @return null 
     */
    public function area(TemplateBuilder $templateBuilder, $areaname) {
        $areaname = strtolower($areaname);
        $atLeastOneContentInThisArea = false;
        
        $contents = $this->response->getContents();
        if(is_array($contents)) {
            foreach($contents as $content) {
                if($content->areaname !== $areaname) continue; // only contents in this area
                if(is_null($this->module($content->module))) continue; // the module is not usable
                
                // there is at least one content in this area
                $atLeastOneContentInThisArea = true; 
                
                // write html of the content
                $this->response->write($content);
            }

            if($this->_canAddModule && !$atLeastOneContentInThisArea) {
                $this->response->write("<div class='cms cms-areaPlaceholder cms-area-$areaname'></div>");
            }
        }
    }
    
    public function defineBackend() {
        
        $dataManager = self::$dataManager;
        $frontendModules = self::$moduleManager->getUsableFrontendModules();
        
        $pageAttributes = $this->data->getAttributes("page", array(
            "*",
            "user" => function($value) {
                if($value) {
                    return $value->username;
                }
            }
        ));
        if(!empty($pageAttributes)) {
            $pageId = $this->session->getParameter("page");
            $pageIndex = -1;
            foreach($pageAttributes as $index => $pageAttribute) {
                if($pageId == $pageAttribute["id"]) {
                    $pageIndex = $index;
                    break;
                }
            }
            $page = array_splice($pageAttributes, $pageIndex, 1);
            array_unshift($pageAttributes, $page[0]);
        }
        
        
        // ADD PAGE
        $this->framework
            ->startGroup("LazyMainTab", array(
                "title" => "Add page",
                "validate" => "all",
                "groupEvents" => true
            ))
                ->input("Textbox", array(
                    "label" => "url",
                    "validators" => array(
                        "notEmpty" => true
                    ),
                    "events" => array(
                        "event" => "addPage"
                    )
                ))
                ->input("Textbox", array(
                    "label" => "title",
                    "events" => array(
                        "event" => "addPage"
                    )
                ));
        
        // PAGES
        $this->framework
            ->startGroup("LazyMainTab", array("title" => "Pages"))
                ->list("DataPager", array(
                    "display" => array(
                        "selectable" => false,
                        "renderer" => array(
                            "name" => "PageRenderer",
                            "events" => array(
                                "event" => "editPage"
                            )
                        ),
                        "scrollable" => false,
                        "groupEvents" => "pages"
                    ),
                    "data" => $pageAttributes
                ));
        
        // CONTENTS
        $this->framework
            ->startGroup("LazyMainTab", array("title" => "Contents"))
                ->startGroup("LazySelectGroup", array("label" => "page"));
        
        foreach($pageAttributes as $page) {
            $this->framework
                ->startGroup("TitledSection", array("title" => $page["url"]))
                    ->list("DataPager", array(
                        "display" => array(
                            "renderer" => array(
                                "name" => "ContentRenderer",
                                "events" => array(
                                    "event" => "editContent"
                                )
                            ),
                            "scrollable" => false,
                            "groupEvents" => "contents"
                        ),
                        "data" => array(
                            "event"  => "getContentsOfPage",
                            "module" => "page",
                            "info"   => array(
                                "id" => $page["id"]
                            )
                        )
                    ))
                    ->end();
        }
        
        // CONTENT ELEMENTS
        $this->framework
            ->startGroup("LazyMainTab", array("title" => "Content elements"))
                ->startGroup("LazySelectGroup", array("label" => "module"));
        
        
        foreach($frontendModules as $name => $module) {
            $this->framework
                ->startGroup("TitledSection", array("title" => $module->information["title"]))
                    ->list("DataPager", array(
                        "display" => array(
                            "renderer" => array(
                                "name" => "ContentElementRenderer",
                                "events" => array(
                                    "event" => "editElement",
                                    "info" => array(
                                        "module" => $name
                                    )
                                ),
                            ),
                            "scrollable" => false,
                            "groupEvents" => "elements"
                        ),
                        "data" => array(
                            "event"  => "getContentElementsByType",
                            "module" => "page",
                            "info"   => array(
                                "module" => $name
                            )
                        )
                    ))
                    ->end();
        }
    }
    
    public function newVariablesEditor(Event $event) {
        $events = array(
            "event" => "edit",
            "module" => "page"
        );
        
        $this->framework
            ->key("elements");
        
        $first = true;
        $vars = Object::merge($this->getTemplateVariables(), json_decode($this->data->find("page", $this->session->getParameter("page"))->vars, true));
        if(!empty($vars)) {
            $this->framework
                ->startGroup("LazyTitledGroup", array("title" => "existing variables", "groupEvents" => true))
                    ->startGroup("TabGroup", array("groupEvents" => "variables"));
            
            foreach($vars as $key => $var) {
                $editorData = explode(".", $var["editor"]);
                $type = $editorData[0];
                $name = $editorData[1];
                
                $this->framework
                    ->startGroup("TitledSection", array("title" => $key, "groupEvents" => true))
                        ->$type($name, array(
                            "label" => "value",
                            "value" => $var["value"],
                            "events" => array(
                                "event" => "edit",
                                "module" => "page",
                                "info" => array("key" => $key)
                            )
                        ))
                        ->input("Checkbox", array(
                            "label" => "delete",
                            "events" => array(
                                "event" => "edit",
                                "module" => "page",
                                "info" => array("key" => $key)
                            )
                        ))
                        ->end();
            }
            
            $this->framework
                ->end()
                ->end();
        }
        $this->framework
            ->startGroup("LazyAddGroup", array(
                "groupEvents" => true,
                "group" => array(
                    "groupEvents" => "newVariables"
                ),
                "events" => $events
            ))
                ->startGroup("Section", array(
                    "groupEvents" => true,
                    "events" => $events
                ))
                    ->input("Textbox", array(
                        "label" => "key",
                        "events" => $events
                    ))
                    ->input("Textbox", array(
                        "label" => "value",
                        "events" => $events
                    ))
                    ->list("Selector", array(
                        "label" => "editor",
                        "data" => array(
                            array(
                                "id" => "input.Textbox",
                                "value" => "Textbox"
                            ),
                            array(
                                "id" => "input.ColorPicker",
                                "value" => "ColorPicker"
                            ),
                            array(
                                "id" => "input.DatePicker",
                                "value" => "DatePicker"
                            )
                        ),
                        "events" => $events
                    ));
    }
    
    public function menu() {
        return array(
            "pages->add"  => "Add page",
            "pages->list" => "Pages"
        );
    }
    
    public function revert($data, $revision) {
        $pageContent = $this->data->find_by_module_and_moduleid("content", "page", $data->id);
        if(!is_null($pageContent)) {
            $contents = $this->data->all("content", array("conditions" => array("module != 'page' && (content_id IS NULL OR content_id = ?)", $pageContent->id)));
        
            foreach($contents as $content) {
                $module = $this->module($content->module);
                if(!is_null($module)) {
                    $d = $module->data->find_by_id($module->name, $content->moduleid);
                    if(!is_null($d)) {
                        $module->revert($d, $revision);
                        
                        $this->dispatchEvent("content", array("data" => $d));
                    }
                }
            }
        }
        
        parent::revert($data, $revision);
    }
}
?>