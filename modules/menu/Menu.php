<?php
/**
 * Module Menu
 * 
 * PHP version 5.3
 * 
 * @author  Friedolin Förder <friedolin.foerder@pinion-cms.org>
 * @license MIT License
 * @link    http://www.pinion-cms.org/module/menu
 */

namespace modules\menu;

use \pinion\modules\FrontendModule;
use \pinion\events\Event;
use \pinion\general\TemplateBuilder;

class Menu extends FrontendModule {
    
    protected $activeParent = null;
    
    

    public function install() {
        $this->data
            ->createDataStorage("menu", array(
                "name" => array("type" => "varchar", "length" => 100, "translatable" => false),  
            ))
            ->createDataStorage("menuitem", array(
                "title"    => array("type" => "varchar", "length" => 100),
                "url"      => array("type" => "varchar", "length" => 250, "translatable" => false),
                "position" => array("type" => "int"),
                "menu",
                "menuitem",
                "page" => "page"
            ));
    }
    
    public function _addFlatMenuitem(array &$flatList, $menuitem) {
        
        $parent = $menuitem->parent;
        
        $new = false;
        if(!isset($flatList[$menuitem->id])) {
            $new = true;
            
            $url = $menuitem->url;
            if($menuitem->title == "") {
                if(is_null($menuitem->page_id)) {
                    $page = $this->module("page")->data->find_by_url("page", $url);
                } else {
                    $page = $menuitem->page;
                }
                
                if(is_object($page)) {
                    $url = $page->url;
                    $title = $page->title ?: $url;
                } else {
                    $title = $url;
                }
            } else {
                $title = $menuitem->title;
            }
            
            $active = ($this->request->getGetParameter("page") == $url) ? 1 : 0;
            $parentid = $parent ? $parent->id : null;
            if($active && $parentid != null) {
                $this->activeParent = $parent->id;
            }
            
            // create menuitem
            $flatList[$menuitem->id] = array(
                "active" => $active,
                "activeBranch" => $active,
                "title" => $title,
                "url" => $url,
                "position" => $menuitem->position,
                "children" => array(),
                "parent" => $parentid
            );
        }
        
        if($parent) {
            // if the menuitem is old, it has all his parents, so we don't have to call this function recursively
            if($new)
                $this->_addFlatMenuitem($flatList, $parent);
            
            $flatList[$parent->id]["children"][] = $menuitem->id;
            $flatList[$parent->id]["children"] = array_unique($flatList[$parent->id]["children"]);
        }
    }
    
    private function _addRecursiveMenuitem(array &$tree, $id, array $items) {
        $tree[$id] = $items[$id];
        $tree[$id]["children"] = array();
        
        foreach($items[$id]["children"] as $child) {
            $this->_addRecursiveMenuitem($tree[$id]["children"], $child, $items);
        }
        
        $this->sortMenuitems($tree[$id]["children"]);
    }
    
    protected function sortMenuitems(array &$list) {
        uasort($list, function($a, $b) {
            $a = $a["position"];
            $b = $b["position"];
            
            if ($a == $b) {
                return 0;
            }
            return ($a < $b) ? -1 : 1;
        });
    }
    
    protected function _buildActiveBranch(array &$list) {
        if(empty($this->activeParent)) return;
        
        while($this->activeParent) {
            $list[$this->activeParent]["activeBranch"] = 1;
            $this->activeParent = $list[$this->activeParent]["parent"];
        }
        
        $this->activeParent = null;
    }
    
    public function orderMenuitems(&$menuitems) {
        $flatList = array();
        $mainMenuitems = array();
        foreach($menuitems as $menuitem) {
            $this->_addFlatMenuitem($flatList, $menuitem);
            if(!$menuitem->parent)
                $mainMenuitems[$menuitem->id] = $flatList[$menuitem->id];
        }
        $this->_buildActiveBranch($flatList);
        // sort main menuitems
        $this->sortMenuitems($mainMenuitems);
        
        $recursiveTree = array();
        foreach($mainMenuitems as $id => $menuitem) {
            $this->_addRecursiveMenuitem($recursiveTree, $id, $flatList);
        }
        
        $menuitems = $recursiveTree;
    }

    public function setFrontendVars($data) {
        
        $menuitems = $data->menuitems;
        $this->orderMenuitems($menuitems);

        return array(
            "name" => $data->name,
            "menuitems" => $menuitems,
        );
    }

    public function add(Event $event) {
        
        $menu = $this->data->create("menu", array(
            "name" => $event->getInfo("name")
        ));
        
        if($event->hasInfo("data")) {
            $menuitems = $event->getInfo("data");
            
            $menuItemsIdMapping = array();
            
            
            
            foreach($menuitems as $menuitem) {
                $url = $menuitem["url"];
                $title = isset($menuitem["title"]) ? trim($menuitem["title"]) : "";
                $page = $this->module("page")->data->find_by_url("page", $menuitem["url"]);
                
                $menuitemData = $this->data->create("menuitem", array(
                    "title" => $title,
                    "url" => $url,
                    "page_id" => is_object($page) ? $page->id : null,
                    "position" => 0,
                    "menu_id" => $menu->id,
                    "menuitem_id" => is_null($menuitem["menuitem_id"]) ? null : $menuItemsIdMapping[$menuitem["menuitem_id"]]
                ));
                $menuItemsIdMapping[$menuitem["id"]] = $menuitemData->id;
            }
        }
        
        $this->module("page")->setModuleContentMapping($event, $menu);

        // add success message
        $this->response->addSuccess($this, $this->translate("%s %s added", "<b>".$this->translate($this->information["title"])."</b>", "<b>{$menu->name}</b>"));
    }
    
    public function remove(Event $event) {
        
        $menu = $event->getInfo("menuname");
        
        
        if(! $this->getMenu($menu)) return;
            
        self::$moduleManager->page->deleteContent($this->name, $menu->id);
        $menu->delete();
        
    }

    protected function getMenu(&$menuidentifier) {
        $menu = null;
        if(is_int($menuidentifier)) {
            $menu = \Menu::find($menuidentifier);
        }
        elseif(is_numeric($menuidentifier)) {
            $id = (int) $menuidentifier;
            $menu = \Menu::find($id);
        }
        elseif(is_string($menuidentifier)) {
            $menu = \Menu::find_by_name($menuidentifier); 
        }
        
        if($menu == null) {
            $this->response->addError($this, "An Error occured by getting the id of a menu with the name '$menuidentifier'.");
            return false;
        }

        $menuidentifier = $menu;
        return true;
    }
    
    public function getResources() {
        return array_merge(parent::getResources(), array(
            "edit menu item",
            "delete menu item",
            "sort menu item"
        ));
    }
    
    public function addListener() {
        parent::addListener();
        
        $this->addEventListener("getMenuItems");
    }
    
    public function preview($data) {
        
        $findChildren = function($menuitem) use (&$findChildren) {
            $children = $menuitem->children;
            $childrenArray = array();
            foreach($children as $child) {
                $childrenArray[] = $findChildren($child);
            }
            return array(
                "title" => $menuitem->title,
                "url" => $menuitem->url,
                "children" => $childrenArray
            );
        };
        
        $self = $this;
        
        return $data->attributes(array(
            "*",
            "items" => function($ar) use ($self, $findChildren) {
                $menuitems = $self->data->find_all_by_menuitem_id_and_menu_id("menuitem", null, $ar->id);
                $items = array();
                foreach($menuitems as $menuitem) {
                    $items[] = $findChildren($menuitem);
                }
                return $items;
            }
        ));
    }
    
    public function getMenuItems(Event $event) {
        
        $menu = $this->data->find("menu", $event->getInfo("id"));
        
        $this->response
            ->setInfo("menuitems", $this->data->getAttributes($menu->menuitems, array(
                "title",
                "url",
                "position",
                "updated",
                "created",
                "menuitem_id"
            )));
    }
    
    /**
     * Template render function: Prints out the current level of the menu
     * 
     * @param \IsolatedTemplateBuilder $templateBuilder
     * @param array [Optional] Options for rendering the level attribute
     * @return mixed Return string or prints out the content 
     */
    public function level(TemplateBuilder $templateBuilder, array $options = array()) {
        $level = count($templateBuilder->getRecursivePath());
        $output = "level-$level";
        
        // the info print
        $info =  array(
            "min"        => "set the minimum level {1}",
            "max"        => "set the maximum level {3}",
            "range"      => "set the minimum and the maximum level {array(0,2)}",
            "wrap"       => "wrap the level with a string {class='|'}",
            "wrapString" => "the string, which would be replaced with the wrap {LEVEL}",
            "classNames" => "set the level names {array(first, second, third)}",
            "print" => "boolean; you can print or return the level {true}"
        );
        
        if(isset($options["help"])) {
            print "<pre>".print_r($info, true)."</pre>";
            return;
        }
        
        // the default range
        $min = 0;
        $max = 10000;
        // set the minimum level
        if(isset($options["min"])) {
            $min = $options["min"];
        }
        // set the maximum level
        if(isset($options["max"])) {
            $max = $options["max"];
        }
        // set the minimum and the maximum via range
        if(isset($options["range"]) && is_array($options["range"]) && count($options["range"]) == 2) {
            $min = $options["range"][0];
            $max = $options["range"][1];
        }
        // if the level is not in the range, don't show the level
        if($level < $min || $level > $max) return;
        
        // set the class names in a row
        if(isset($options["classNames"]) && isset($options["classNames"][$level-$min])) {
            $output = $options["classNames"][$level-$min];
        }
        
        // you could set the string, which would be replaced with the wrap
        $wrapString = "|";
        if(isset($options["wrapString"])) {
            $wrapString = $options["wrapString"];
        }
        
        // you could wrap the 
        if(isset($options["wrap"])) {
            $output = str_replace($wrapString, $output, $options["wrap"]);
        }
        
        // the variable print decide, if the variable should be printed or only be returned
        if(isset($options["print"]) && $options["print"] == true) {
            print $output;
        } else {
            return $output;
        }
    }
    
    /**
     * Template render function: Prints out a string (default is 'active'), when the link is active
     * 
     * @param TemplateBuilder $templateBuilder
     * @param array $options 
     */
    public function active(TemplateBuilder $templateBuilder, array $options = array()) {
        $output = "";
        
        // standard active/inactive strings
        $active = "active";
        $inactive = "inactive";
        
        // set active/inactive strings
        $active = isset($options["active"]) ? $options["active"] : $active;
        $inactive = isset($options["inactive"]) ? $options["inactive"] : $inactive;
        
        // set both class names in a row
        if(isset($options["classNames"]) && count($options["classNames"] > 0)) {
            $active = $options["classNames"][0];
            $inactive = isset($options["classNames"][1]) ? $options["classNames"][1] : "";
        }
        
        // decide, which class class should be returned
        if(SITE_URL.self::$moduleManager->page->url == $templateBuilder->get("url"))
            $output = $active;
        else
            $output = $inactive;
        
        // the variable print decide, if the variable should be printed or only be returned
        if(isset($options["print"]) && $options["print"] == true) {
            print $output;
        } else {
            return $output;
        }
    }
    
    public function edit(Event $event) {
        $data = $event->getInfo("data");
        $id = $event->getInfo("id");
        
        $menu = $this->data->find("menu", $id);
        
        foreach($data as $menuData) {
            if(isset($menuData["isNew"])) {
                if(isset($menuData["deleted"]) && $menuData["deleted"] == true) {
                    continue;
                }
                
                $url = $menuData["url"];
                $title = isset($menuData["title"]) ? trim($menuData["title"]) : "";
                $page = $this->module("page")->data->find_by_url("page", $url);
                
                if($title == "") {
                    $title = is_object($page) ? ($page->title ?: $url) : $url;
                }
                
                $menuitem = $this->data->create("menuitem", array(
                    "title" => $title,
                    "url" => $url,
                    "page_id" => is_object($page) ? $page->id : null,
                    "position" => $menuData["position"],
                    "menuitem_id" => $menuData["menuitem_id"],
                    "menu_id" => $id
                ));
            } else {
                $menuitem = $this->data->find("menuitem", $menuData["id"]);
                if(isset($menuData["deleted"]) && $menuData["deleted"] == true) {
                    $menuitem->delete();
                } else {
                    if(isset($menuData["url"]) && $menuData["url"] != $menuitem->url) {
                        $url = $menuData["url"];
                        $title = isset($menuData["title"]) ? trim($menuData["title"]) : "";
                        $page = $this->module("page")->data->find_by_url("page", $url);
                        
                        $menuitem->url = $url;
                        $menuitem->title = $title;
                        
                        $menuitem->page_id = is_object($page) ? $page->id : null;
                        
                    } elseif(isset($menuData["title"]) && $menuData["title"] != $menuitem->title) {
                        $menuitem->title = $menuData["title"];
                    }
                    
                    $menuitem->position = $menuData["position"];
                    $menuitem->menuitem_id = $menuData["menuitem_id"];
                    $menuitem->menu_id = $id;
                    
                    $menuitem->save();
                }
            }
            
            
        }
        
        // dispatch content event to update the contents on the page
        $this->module("page")->dispatchEvent("content", array("data" => $menu));
        
        $this->response->addSuccess($this, $this->translate("%s edited", "<b>".$this->translate($this->information["title"])."</b>"));
    }
}
?>
