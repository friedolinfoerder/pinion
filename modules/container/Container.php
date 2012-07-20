<?php
/**
 * Module Container
 * 
 * PHP version 5.3
 * 
 * @author  Friedolin Förder <friedolin.foerder@pinion-cms.org>
 * @license MIT License
 * @link    http://www.pinion-cms.org/module/container
 */

namespace modules\container;

use \pinion\modules\FrontendModule;
use \pinion\events\Event;
use \pinion\general\TemplateBuilder;
use \pinion\data\models\Page_content;

class Container extends FrontendModule {
    
    public function install() {
        $this->data
            ->createDataStorage("container", array(
                "structure"
            ))
            ->createDataStorage("element", array(
                "module"   => array("type" => "varchar", "length" => 100, "translatable" => false),
                "moduleid" => array("type" => "int"),
                "position" => array("type" => "int"),
                "container"
            ))
            ->createDataStorage("structure", array(
                "name" => array("type" => "varchar", "length" => 100)
            ))
            ->createDataStorage("content", array(
                "module"   => array("type" => "varchar", "length" => 100, "translatable" => false),
                "howmany"  => array("type" => "int"),
                "position" => array("type" => "int"),
                "templatepath" => array("type" => "varchar", "length" => 100, "translatable" => false),
                "structure"
            ));
        
        $this->module("translation")->setBackendTranslation("de", array(
            "Container Module" => "Containermodul"
        ));
    }

    
    public function getResources() {
        return array_merge(parent::getResources(), array(
            "add structure",
            "delete structure",
            "rename structure",
            "sort structure"
        ));
    }
    
    public function addListener() {
        parent::addListener();
        
        
        
        if($this->identity) {
            $this->addEventListener("getStructures");
            $this->addEventListener("hasStructureName");
            if($this->hasPermission("add structure")) $this->addEventListener("addStructure");
            if($this->hasPermission("delete structure")) $this->addEventListener("deleteStructure");
            if($this->hasPermission("rename structure")) $this->addEventListener("renameStructure");
            if($this->hasPermission("sort structure")) $this->addEventListener("sortStructure");
            
            $this->module("page")->addEventListener("changeData", "editData", $this);
        }
    }
    
    
    public function deleteStructure(Event $event) {
        $deleted = $event->getInfo("deleted");
        
        if($deleted) {
            $id = $event->getInfo("id");
            
            $this->data->massUpdate("container", array(
                "deleted" => 1
            ), array(
                "structure_id" => $id,
                "instance_id" => null
            ));
            $this->data->find("structure", $id)->delete();
        }
    }
    
    
    public function editData(Event $event) {
        $data = $event->getInfo("data");
        $id = $data->id;
        $module = $data->module()->name;
        
        $elements = $this->data->find_all_by_module_and_moduleid("element", $module, $id);
        $container = array();
        foreach($elements as $element) {
            if(!isset($container[$element->container_id])) {
                $con = $element->container;
                $container[$con->id] = $con;
                
                // dispatch content event to update the contents on the page
                $this->module("page")->dispatchEvent("content", array("data" => $con));
            }
        }
    }
    
    public function sortStructure(Event $event) {
        
        $pageContentData = $this->module("page")->data;
        
        $id = $event->getInfo("id");
        $structure = $this->data->find("structure", $id);
        $containers = $structure->containers;
        $containerIds = array();
        $pageContentIds = array();
        
        foreach($containers as $container) {
            $containerId = $container->id;
            
            // add to container ids
            $containerIds[] = $containerId;
            
            // find container page contents
            $contents = $pageContentData->find_all_by_module_and_moduleid_and_content_id("content", "container", $containerId, $this->session->getParameter("pageContent"));
            foreach($contents as $content) {
                $pageContentIds[] = $content->id;
            }
        }
        
        $data = $event->getInfo("data");
        
        if(!empty($containerIds)) {
            // reset positions (-10000 + value)
            $this->data->massUpdate("element", "position = -10000+position", array(
                "container_id" => $containerIds,
                "instance_id" => null
            ));
        }
        
        $oldPosition = 0;
        $structureContents = $structure->contents;
        
        $newPositions = array();
        foreach($data as $index => $d) {
            $newPositions[$d["id"]] = $index;
        }
        
        // elements to content position mapping
        $positionMapping = array();
        
        foreach($structureContents as $index => $structureContent) {
            $newPosition = $newPositions[$structureContent->id];
            // update structure content
            $structureContent->position = $newPosition;
            $structureContent->save();
            
            
            if(!empty($containerIds)) {
                $howmany = $structureContent->howmany;
                // save the old (start) position and how many elements in a row
                // in the position mapping array with the new position index
                $positionMapping[$newPosition] = array(
                    "howmany" => $howmany,
                    "oldPosition" => $oldPosition
                );
                $oldPosition += $howmany;
            }
        }
        
        // update elements
        if(!empty($containerIds)) {
            $position = 0;
            for($i = 0, $length = count($positionMapping); $i < $length; $i++) {
                
                // get the old (start) position and how many elements in a row
                $howmany = $positionMapping[$i]["howmany"];
                $oldPosition = $positionMapping[$i]["oldPosition"];
                
                for($j = 0; $j < $howmany; $j++) {
                    $this->data->massUpdate("element", array(
                        "position" => $position++
                    ), array(
                        "position" => -10000+$oldPosition+$j,
                        "container_id" => $containerIds,
                        "instance_id" => null
                    ));
                }

                $oldPosition++;
            }
        }
        
        // update page contents
        foreach($pageContentIds as $contentId) {
            $this->module("page")->dispatchEvent("content", array(
                "id" => $contentId
            ));
        }
        
    }
    
    
    public function preview($data) {
        $_this = $this;
        return $data->attributes(array(
            "structure" => array(
                "name",
                "contents"
            ),
            "elements"
        ));
    }
    
    public function addStructure(Event $event) {
        
        $name = $event->getInfo("name");
        $data = $event->getInfo("data");
        
        $structure = $this->data->create("structure", array(
            "name" => $name,
        ));
        foreach($data as $index => $d) {
            $this->data->create("content", array(
                "module"   => $d["moduleid"],
                "howmany"  => $d["how many"],
                "structure_id" => $structure->id,
                "position" => $index,
                "templatepath" => $d["template"]
            ));
        }
        
        $this->response->addSuccess($this, $this->translate("The structure %s was created", "<b>".$name."</b>"));
    }
    
    
    
    public function renameStructure(Event $event) {
        
        $id = $event->getInfo("id");
        $name = $event->getInfo("name");
        
        $structure = $this->data->find("structure", $id);
        if($structure) {
            $structure->name = $name;
            $structure->save();
        }
        
        $this->response->addSuccess($this, $this->translate("The name of the structure %s was updated", "<b>".$name."</b>"));
    }
    
    
    
    public function hasStructureName(Event $event) {
        
        $message = is_null($this->data->find_by_name("structure", $event->getInfo("name"))) ?: "The structure name is already in use";
        
        $this->response->setInfo("valid", $message);
    }
    
    
    
    public function add(Event $event) {
        
        $container = $this->data->create("container", array(
            "structure_id" => $event->getInfo("structure")
        ));
        $containerId = $container->id;
        
        $existingModules = $event->getInfo("existingModules");
        
        foreach($existingModules as $position => $moduleInfo) {
            $this->data->create("element", array(
                "module"       => $moduleInfo["module"],
                "moduleid"     => $moduleInfo["id"],
                "container_id" => $containerId,
                "position"     => $position
            ));
        }
        
        $this->module("page")->setModuleContentMapping($event, $container);
        
        $self = $this;
        $modules = $event->getInfo("modules");
        $id = $container->id;
        $elements = array();
        
        // collect all contents
        $this->module("page")->addEventListener("contentMapping", function($event) use($self, $modules, &$id, $containerId, &$elements) {
            
            $identifier = $event->getInfo("identifier");
            $content = $event->getInfo("content");
            $module = $event->getInfo("module");
            
            if(isset($identifier) && array_key_exists($identifier, $modules)) {
                $self->data->create("element", array(
                    "module"       => $module,
                    "moduleid"     => $content->id,
                    "container_id" => $containerId,
                    "position"     => $modules[$identifier]
                ));
            }
        });
        
        // add success message
        $this->response->addSuccess($this, $this->translate("%s added", "<b>".$this->translate($this->information["title"])."</b>"));
    }
    
    
    public function edit(Event $event) {
        $replace = $event->getInfo("replace");
        if(!empty($replace)) {
            $container = $this->data->find_by_id("container", $event->getInfo("id"));
            if(is_null($container)) {
                // add error message
                return $this->response->addError($this, $this->translate("%s not exists", "<b>".$this->translate($this->information["title"])."</b>"));
            }
            $elements = $container->elements;
            foreach($replace as $position => $id) {
                $elements[$position]->moduleid = $id;
                $elements[$position]->save();
            }

            // dispatch content event to update the contents on the page
            $this->module("page")->dispatchEvent("content", array("data" => $container));

            // add success message
            $this->response->addSuccess($this, $this->translate("%s edited", "<b>".$this->translate($this->information["title"])."</b>"));
        }
        
    }
    
    
    public function getStructures(Event $event) {
        $this->response->setInfo("data", $this->data->getAttributes("structure", array(
            "name",
            "contents" => array(
                "module",
                "howmany"
            )
        )));
    }
    
    
    
    public function setFrontendVars($data) {
        
        $structureId = $data->structure_id;
        
        return $data->attributes(array(
            "*",
            "elements" => array(
                "*",
                "structure" => $structureId
            )
        ));
    }
    
    /**
     * Template Render Function: Prints out the html of the content
     * @param Page_content The content of the element
     * @param string An string with classes 
     * @return null 
     */
    public function content(TemplateBuilder $templateBuilder, $element) {
        $module = $this->module($element["module"]);
        if($module) {
            $content = new \stdClass();
            $structureContent = $this->data->find_by_structure_id_and_position("content", $element["structure"], $element["position"]);
            $content->templatepath = is_object($structureContent) ? $structureContent->templatepath : "standard";
            $content->moduleid = $element["moduleid"];
            $content->id = -1;
            print $templateBuilder->renderTemplateOfRenderer("_main", $module, $content);
        }
    }
    
    
    
    public function defineBackend() {
        parent::defineBackend();
        
        $usableModules = self::$moduleManager->getUsableModules();
        $modules = array();
        foreach($usableModules as $usableModule) {
            if($usableModule instanceof FrontendModule && $usableModule->hasPermission("add")) {
                $modules[] = array(
                    "id" => $usableModule->name,
                    "title" => $usableModule->information["title"]
                );
            }
        }
        
        $self = $this;
        
        $structures = $this->data->getAttributes("structure", array(
            "name",
            "contents" => array(
                "module" => function($value) use ($self) {
                    return $self::$moduleManager->getTitle($value);
                },
                "howmany" => function($value) {
                    return $value ?: "any";
                }
            )
        ));
        
        // TAB ADD STRUCTURE
        if($this->hasPermission("add structure")) {
            $this->framework
                ->startGroup("LazyMainTab", array("title" => "Add structure"))
                    ->startGroup("ColumnGroup")
                        ->startGroup("Section", array(
                            "validate" => "all",
                            "groupEvents" => true
                        ))
                            ->input("Textbox", array(
                                "label" => "name",
                                "validators" => array(
                                    "events" => array(
                                        "event" => "hasStructureName"
                                    ),
                                    "notEmpty" => true
                                ),
                                "data" => $modules,
                                "events" => array(
                                    "event" => "addStructure"
                                )
                            ))
                            ->list("Finder", array(
                                "identifier" => "structureList",
                                "events" => array(
                                    "event" => "addStructure"
                                )
                            ))
                            ->end()
                        ->startGroup("Section")
                            ->list("Selector", array(
                                "label" => "module",
                                "data" => $modules
                            ))
                            ->input("Slider", array(
                                "label" => "how many",
                                "value" => 1,
                                "min"   => 1,
                                "max"   => 10
                            ))
                            ->list("Selector", array(
                                "label" => "template",
                                "value" => "standard",
                                "noEmptyValue" => true,
                                "description" => "name"
                            ))
                            ->input("Button", array(
                                "identifier" => "containerAddModuleButton",
                                "label" => "add"
                            ))
                            ->end();
        }
        
        
        $this->framework
            ->startGroup("LazyMainTab", array("title" => "Change structures", "open" => false))
                ->list("Finder", array(
                    "data" => $this->data->getAttributes("structure", array(
                        "name",
                        "contents"
                    )),
                    "renderer" => "StructureRenderer"
                ));
        
    }
    
}
?>
