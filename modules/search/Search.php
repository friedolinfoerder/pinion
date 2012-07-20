<?php
/**
 * Module Search
 * 
 * PHP version 5.3
 * 
 * @author  Friedolin Förder <friedolin.foerder@pinion-cms.org>
 * @license MIT License
 * @link    http://www.pinion-cms.org/module/search
 */

namespace modules\search;

use \pinion\modules\Module;
use \pinion\modules\FrontendModule;
use \pinion\events\Event;
use \pinion\general\TemplateBuilder;

class Search extends FrontendModule {
    
    protected $_searchResults = null;
    
    public function install() {
        $this->data
            ->createDataStorage("search", array(
                "with_results" => array("type" => "boolean"),
                "count"        => array("type" => "int"),
                "modules"      => array("type" => "varchar", "length" => 500, "translatable" => false),
                "page"         => "page"
            ))
            ->createDataStorage("archive", array(
                "search" => array("type" => "varchar", "length" => 100),
                "count"  => array("type" => "int")
            ), array(
                "revisions" => false
            ));
    }
    
    public function addListener() {
        parent::addListener();
        
        $this->addEventListener("doSearch");
        if($this->identity) {
            $this->addEventListener("buildEditor");
        }
    }
    
    public function defineEditor(Event $event) {
        $settings = $event->getInfo("settings");
        $data = $settings["data"];
        $isNew = $settings["_isNew"];
        $events = array("event" => $isNew ? "add" : "edit");
        
        $modules = self::$moduleManager->getUsableFrontendModules();
        $moduleArray = array();
        foreach($modules as $name => $module) {
            $moduleArray[] = array("id" => $name, "name" => $this->translate($module->information["title"]));
        }
        
        if(!$isNew && $data["page_id"] != null) {
            $page = $this->module("page")->data->find("page", $data["page_id"]);
            $url = $page->url;
        } else {
            $url = "";
        }
        
        $this->framework
            ->key("elements")
                ->input("AutoCompleteTextbox", array(
                    "label" => "Search site",
                    "infoKey" => "url",
                    "data" => array(
                        "event" => "getUrlData",
                        "module" => "page"
                    ),
                    "value" => $url,
                    "events" => $events
                ))
                ->list("Finder", array(
                    "data" => $moduleArray,
                    "infoKey" => "modules",
                    "scrollable" => true,
                    "selectable" => $isNew ? true : array(
                        "items" => json_decode($data["modules"])
                    ),
                    "multiple" => true,
                    "events" => $events,
                    "groupEvents" => true
                ))
                ->end()
            ->startGroup("TitledSection", array(
                "title" => "Search results",
                "groupEvents" => true,
                "events" => $events,
                "info" => array(
                    "with_results" => true
                )
            ))
                ->input("Checkbox", array(
                    "label" => "with results",
                    "value" => $isNew ? false : $data["with_results"],
                    "events" => $events
                ))
                ->input("Slider", array(
                    "label" => "count",
                    "min" => 10,
                    "max" => 100,
                    "value" => $isNew ? 10 : $data["count"],
                    "events" => $events
                ))
                ->end();
    }
    
    public function doSearch(Event $event) {
        $value = trim($event->getInfo("value"));
        $id = $event->getInfo("id");
        
        $data = $this->data->find("search", $id);
        $modules = json_decode($data->modules);
        
        // add search to archive (count it)
        $archive = $this->data->find_by_search("archive", $value);
        if(is_null($archive)) {
            $this->data->create("archive", array(
                "search" => $value,
                "count" => 1
            ));
        } else {
            $archive->count++;
            $archive->save();
        }
        
        $elements = array();
        $pageData = $this->module("page")->data;
        // find all visible page contents
        $pageContents = $pageData->all("content", array("conditions" => array("visible = ? AND module = ?", 1, "page")));
        $pageContentsArray = array();
        foreach($pageContents as $pageContent) {
            $pageContentsArray[$pageContent->id] = $pageContent;
        }
        
        // find all normal contents (no page contents)
        $contents = $pageData->all("content", array("conditions" => array("visible = ? AND module != ?", 1, "page")));
        $contentsArray = array();
        foreach($contents as $content) {
            // is there a visible page content?
            if(isset($pageContentsArray[$content->content_id])) {
                if(!isset($contentsArray[$content->module])) {
                    $contentsArray[$content->module] = array();
                }
                $contentsArray[$content->module][$content->moduleid] = array(
                    "content" => $content,
                    "pageContent" => $pageContentsArray[$content->content_id]
                );
            }
        }
        
        foreach($modules as $module) {
            $module = $this->module($module);
            
            if($module && isset($contentsArray[$module->name])) {
                $result = $module->dispatchEvent("search", array(
                    "value" => $value
                ));

                if(is_array($result)) {
                    foreach($result as $row) {
                        // is there a visible content?
                        if(isset($contentsArray[$module->name][$row->id])) {
                            // group elements in pages
                            $contentInfo = $contentsArray[$module->name][$row->id];
                            $pageId = $contentInfo["pageContent"]->moduleid;
                            if(!isset($elements[$pageId])) {
                                $elements[$pageId] = array(
                                    "url" => $pageData->find("page", $pageId)->url,
                                    "count" => 0,
                                    "elements" => array()
                                );
                            }
                            $elements[$pageId]["elements"][] = $contentInfo["content"];
                            $elements[$pageId]["count"]++;
                        }
                    }
                }
            }
        }
        
        // sort pages with count property
        usort($elements, function($a, $b) {
            return $a["count"] > $b["count"];
        });
        
        $this->_searchResults = $elements;
        
        if($this->request->isAjax()) {
            $array = array();
            foreach($elements as $content) {
                $array[] = TemplateBuilder::getInstance()->renderTemplateOfRenderer("_main", $this->module($content->module), $content);
            }
            $this->response->setInfo("content", $array);
        }
    }
    
    public function add(Event $event) {
        
        $url = $event->getInfo("url");
        $page = ($url != null) ? $this->module("page")->data->find_by_url("page", $url) : null;
        
        $modules = $event->getInfo("modules");
        if($modules) {
            $modules = json_encode($modules);
        } else {
            $modules = "[]";
        }
        $search = $this->data->create("search", array(
            "with_results" => $event->hasInfo("with_results"),
            "count"        => $event->getInfo("count") ?: 10,
            "modules"      => $modules,
            "page_id"      => is_null($page) ? $this->session->getParameter("page") : $page->id
        ));
        
        $this->module("page")->setModuleContentMapping($event, $search);
        
        // add success message
        $this->response->addSuccess($this, $this->translate("%s added", "<b>".$this->translate($this->information["title"])."</b>"));
    }
    
    public function edit(Event $event) {
        $id = $event->getInfo("id");
        $data = $this->data->find("search", $id);
        if($event->hasInfo("with_results")) {
            $data->with_results = $event->getInfo("with_results");
        }
        if($event->hasInfo("url")) {
            $url = $event->getInfo("url");
            $page = $this->module("page")->data->find_by_url("page", $url);
            $data->page_id = is_null($page) ? $this->session->getParameter("page") : $page->id;
        }
        $data->save();
        
        // dispatch content event to update the contents on the page
        $this->module("page")->dispatchEvent("content", array("data" => $data));
        
        // add success message
        $this->response->addSuccess($this, $this->translate("%s edited", "<b>".$this->translate($this->information["title"])."</b>"));
    }
    
    public function defineBackend() {
        parent::defineBackend();
        
        $searches = $this->data->all("archive", array("order" => "count DESC", "limit" => 25));
        $sum = $this->data->find("archive", array("select" => "sum(count) as sum"));
        $sum = $sum->sum;
        
        if(isset($searches[0])) {
            $biggestCount = $searches[0]->count;
            
            $searchData = $this->data->getAttributes($searches, array(
                "*",
                "procent" => function($ar) use($sum) {
                    return $sum == 0 ? 0 : sprintf("%1.2f", $ar->count / $sum * 100);
                },
                "bar" => function($ar) use($biggestCount) {
                    return $biggestCount == 0 ? 0 : sprintf("%1.2f", $ar->count / $biggestCount * 100);
                }
            ));
        } else {
            $searchData = array();
        }
        
        
        
        $this->framework
            ->startGroup("LazyMainTab", array("title" => "Search archive"))
                ->list("Finder", array(
                    "data" => $searchData,
                    "renderer" => array(
                        "name" => "SearchRenderer"
                    )
                ));
    }
    
    public function setFrontendVars($data) {
        $page = $data->page;
        $array = array(
            "id" => $data->id,
            "pageUrl" => $page ? $page->url : "",
            "domClass" => "search-results",
            "domId" => "search-{$data->id}"
        );
        
        if($data->with_results) {
            return array_merge($array, array(
                "with_results" => true,
                "domId" => "search-{$data->id}",
                "domClass" => "search-results",
                "searched" => !is_null($this->_searchResults),
                "results" => $this->_searchResults
            ));
        } else {
            return array_merge($array, array(
                "with_results" => false,
            ));
        }
        
    }
    
    public function preview($data) {
        return $data->attributes(array(
            "*",
            "page",
            "modules" => function($value) {
                return json_decode($value);
            }
        ));
    }
    
    /**
     * Template Render Function: Prints out the html of the content
     * @param Page_content The content of the element
     * @param string An string with classes 
     * @return null 
     */
    public function content(TemplateBuilder $templateBuilder, $element) {
        $module = $this->module($element->module);
        if($module) {
            $content = new \stdClass();
            $content->templatepath = $element->templatepath;
            $content->moduleid = $element->moduleid;
            $content->id = -1;
            print $templateBuilder->renderTemplateOfRenderer("_main", $module, $content);
        }
    }
}


?>