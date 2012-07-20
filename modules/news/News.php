<?php
/**
 * Module News
 * 
 * PHP version 5.3
 * 
 * @author  Friedolin Förder <friedolin.foerder@pinion-cms.org>
 * @license MIT License
 * @link    http://www.pinion-cms.org/module/news
 */

namespace modules\news;

use \pinion\modules\FrontendModule;
use \pinion\data\models\Page_content;
use \pinion\events\Event;
use \pinion\general\TemplateBuilder;

/**
 * Description of News
 * 
 * @category   data
 * @package    pinion
 * @subpackage database
 * @author     Friedolin Förder <friedolinfoerder@pinion-cms.de>
 * @license    license placeholder
 * @link       http://www.pinion-cms.de
 */
class News extends FrontendModule {
    
    public function install() {
        $this->data
            ->createDataStorage("news", array(
                "count"        => array("type" => "int"),
                "templatePath" => array("type" => "varchar", "length" => 100, "isNull" => true, "translatable" => false),
                "structure"    => "container"
            ));
        
    }
    
    public function addListener() {
        parent::addListener();
        
        if($this->identity) {
            $this->module("page")->addEventListener("changeData", "editData", $this);
        }
    }
    
    public function editData(Event $event) {
        $data = $event->getInfo("data");
        $id = $data->id;
        $module = $data->module()->name;
        if($module == "container") {
            $news = $this->data->find_all_by_structure_id("news", $data->structure_id);
            foreach($news as $n) {
                // dispatch content event to update the contents on the page
                $this->module("page")->dispatchEvent("content", array("data" => $n));
            }
        }
    }
    
    public function add(Event $event) {
        $news = $this->data->create("news", array(
            "structure_id" => $event->getInfo("structure"),
            "count"        => $event->hasInfo("count") ? $event->getInfo("count") : 10,
            "templatepath" => $event->getInfo("template")
        ));
        
        $this->module("page")->setModuleContentMapping($event, $news);
        
        $this->response->addSuccess($this, $this->translate("%s added", "<b>".$this->translate($this->information["title"])."</b>"));
    }
    
    
    
    public function edit(Event $event) {
        $news = $this->data->find("news", $event->getInfo("id"));
        if($event->hasInfo("structure")) {
            $news->structure_id = $event->getInfo("structure");
        }
        if($event->hasInfo("count")) {
            $news->structure_id = $event->getInfo("count");
        }
        if($event->hasInfo("template")) {
            $news->templatepath = $event->getInfo("template");
        }
        
        $news->save();
        
        // dispatch content event to update the contents on the page
        $this->module("page")->dispatchEvent("content", array("data" => $news));
        
        $this->response->addSuccess($this, $this->translate("%s edited", "<b>".$this->translate($this->information["title"])."</b>"));
    }
    
    
    
    public function setFrontendVars($data) {
        $elements = array();
        $containers = array();
        
        // find structure
        $structure = $data->structure;
        
        if($structure) {
            // get containers of structure
            $containers = $this->module("container")->data->all("container", array("conditions" => array("structure_id = ?", $structure->id), "order" => "created DESC", "limit" => $data->__get("count")));
            
            foreach($containers as $container) {
                $elements[] = $container->attributes();
            }
        }
        
        return array(
            "elements" => $elements,
            "templatepath" => $data->templatepath
        );
    }
    
    
    /**
     * Template Render Function: Prints out the html of the content
     * @param array The element
     * @param string An string with classes 
     * @return null 
     */
    public function content(TemplateBuilder $templateBuilder, $element) {
        $module = $this->module("container");
        if($module) {
            $content = new \stdClass();
            $content->templatepath = $templateBuilder->__get("templatepath");
            $content->moduleid = $element["id"];
            $content->id = -1;
            print $templateBuilder->renderTemplateOfRenderer("_main", $module, $content);
        }
    }
    
    
    public function defineBackend() {
        parent::defineBackend();
    }
    
    public function preview($data) {
        $_this = $this;
        return $data->attributes(array(
            "*",
            "structure" => array(
                "name",
                "contents"
            ),
            "news" => function($ar) use ($_this) {
                $news = $_this->setFrontendVars($ar);
                return $news["elements"];
            }
        ));
    }
}

?>
