<?php
/**
 * Module Permissions
 * 
 * PHP version 5.3
 * 
 * @author  Friedolin Förder <friedolin.foerder@pinion-cms.org>
 * @license MIT License
 * @link    http://www.pinion-cms.org/module/permissions
 */
 
namespace modules\permissions;

use \pinion\modules\Module;
use \pinion\events\Event;
use \pinion\authorization\Authorizator;
use \pinion\general\Registry;

class Permissions extends Module {

    protected $user;
    
    public function init() {
        $this->framework->addCollection("FinderDragLeftToRight", array(
            0 => array(
                "type" => "startGroup",
                "name" => "ColumnGroup",
                "open" => true
            ),
                1 => array(
                    "type" => "list",
                    "name" => "Finder",
                    "groupEvents" => true,                    
                    "multiple" => false,
                    "selectable" => false,
                    "draggable" => true
                ),
                2 => array(
                    "type" => "list",
                    "name" => "Finder"
                ),
                3 => array(
                    "type" => "end"
                ),
        ));
    }
    
    public function addListener() {
        parent::addListener();
        
        $this
            ->addEventListener("update")
            ->addEventListener("message")
            ->addEventListener("changeUser")
            ->addEventListener("changeRuleOfUser")
            ->addEventListener("usernameIsUnique")
            ->addEventListener("resources")
            ->addEventListener("updateShortcuts");
        
        
        
        if($this->hasPermission("edit resources of rule")) $this->addEventListener("changeResourcesOfRule");
        
        if($this->hasPermission("add user")) $this->addEventListener("addUser");
        
        if($this->hasPermission("add rule")) $this->addEventListener("addRule");
        if($this->hasPermission("edit rule"))   $this->addEventListener("changePositionsOfRules");
        if($this->hasPermission("delete rule")) $this->addEventListener("removeRule");
    }

    public function install() {
        $this->data
            ->createDataStorage("user", array(
                "username"    => array("type" => "varchar", "length" => 30, "translatable" => false),
                "password"    => array("type" => "varchar", "length" => 40, "translatable" => false),
                "email"       => array("type" => "varchar", "length" => 100, "isNull" => true, "translatable" => false),
                "firstname"   => array("type" => "varchar", "length" => 100, "translatable" => false),
                "lastname"    => array("type" => "varchar", "length" => 100, "translatable" => false),
                "lastupdate"  => array("type" => "int"),
                "hasmessages" => array("type" => "boolean"),
                "language"    => array("type" => "varchar", "length" => 100, "translatable" => false),
                "shortcuts"   => array("type" => "varchar", "length" => 500, "translatable" => false),
                "image"       => array("type" => "text", "isNull" => true, "translatable" => false),
                "rule"
            ))
            ->createDataStorage("rule", array(
                "name" => array("type" => "varchar", "length" => 30, "translatable" => false),
                "position"  => array("type" => "int"),
                "rule"
            ))
            ->createDataStorage("resource", array(
                "permission" => array("type" => "varchar", "length" => 30, "translatable" => false),
                "allow"      => array("type" => "boolean"),
                "module"     => array("type" => "varchar", "length" => 100, "translatable" => false),
                "rule"
            ));
        
        $rule = $this->data->create("rule", array(
            "name" => "Administrators",
            "position" => 0
        ));
        
        $editors = $this->data->create("rule", array(
            "name" => "Editors",
            "position" => 0
        ));
        
        $designer = $this->data->create("rule", array(
            "name" => "Designer",
            "position" => 0,
            "rule_id" => $editors->id
        ));
        
        $this->data->create("rule", array(
            "name" => "Webdesigner",
            "position" => 0,
            "rule_id" => $designer->id
        ));
        
        $this->data->create("rule", array(
            "name" => "Developer",
            "position" => 0,
            "rule_id" => $editors->id
        ));
        
        $username = $this->request->getPostParameter("admin_username") ?: "admin";
        $password = $this->request->getPostParameter("admin_password") ?: "admin";
        $firstname = $this->request->getPostParameter("admin_firstname") ?: "admin";
        $lastname = $this->request->getPostParameter("admin_lastname") ?: "admin";
        $email = $this->request->getPostParameter("admin_email") ?: null;
        $language = $this->request->getPostParameter("admin_language") ?: "en";
        
        
        $this->data->create("user", array(
            "username"    => $username,
            "password"    => $this->generatePassword($password),
            "email"       => $email,
            "firstname"   => $firstname,
            "lastname"    => $lastname,
            "lastupdate"  => 0,
            "hasmessages" => false,
            "rule_id"     => $rule->id,
            "language"    => $language,
            "shortcuts"   => "[]"
        ));
        
        $this->data->create("user", array(
            "username"    => "editor",
            "password"    => $this->generatePassword("editor"),
            "email"       => "editor@editor.org",
            "firstname"   => "editor",
            "lastname"    => "editor",
            "lastupdate"  => 0,
            "hasmessages" => false,
            "rule_id"     => $editors->id,
            "language"    => "en",
            "shortcuts"   => "[]"
        ));
    }
    
    public function addRule(Event $event) {
        $this->data->create("rule", array(
            "name" => $event->getInfo("name"),
            "rule_id" => $event->getInfo("id"),
            "position" => 0
        ));
        
        $this->response->addSuccess($this, $this->translate("%s %s added", $this->translate("rule"), "<b>".$event->getInfo("name")."</b>"));
    }
    
    public function removeRule(Event $event) {
        $rules = $event->getInfo("rules");
        
        foreach($rules as $rule) {
            
            $data = $this->data->find("rule", $rule["id"]);
            
            
            $children = $data->children;
            
            foreach($children as $child) {
                $child->rule_id = null;
                $child->save();
            }
            
            $this->response
                ->addSuccess($this, $this->translate("%s %s deleted", $this->translate("rule"), "<b>".$data->name."</b>"));
            
            // delete
            $data->clearCache();
            
            $data = $this->data->find("rule", $rule["id"]);
            
            $data->delete();
        }
    }
    
    public function update(Event $event) {
        $this->response
            ->setInfo("user", $this->getOnlineUser());
    }
    
    public function getOnlineUser() {
        
        // tolerance
        $tolerance = 3;
        
        $user = $this->data->all("user");
        $onlineUser = array();
        
        foreach($user as $u) {
            if($u->id == $this->identity->userid) {
                Registry::stopRevisioning();
                
                $this->user = $u;
                
                $u->lastupdate = time();
                
                if($u->hasmessages) {
                    $u->hasmessages = false;
                    $this->response->addMessage(null, $this->translate("You've got new messages"), array("type" => "message"));
                }
                $u->save(true, true);
                Registry::startRevisioning();
                
                continue;
            }
            // if the lastupdate property of the user is newer
            // than the current time minus the update interval (minus a tolerance)
            // the user is online
            if($u->lastupdate > time() - Registry::getUpdateInterval() - $tolerance) {
                $onlineUser[$u->id] = $u->attributes(array(
                    "username",
                    "firstname",
                    "lastname",
                    "email"
                ));
            }
        }
        return $onlineUser;
    }
    
    public function resources(Event $event) {
        $ruleId = $event->getInfo("rule");
        
        $rule = $this->data->find("rule", $ruleId);
        $resources = $rule->resources;
        
        $class = $this;
        $translate = function($value) {
            $translationModule = \pinion\modules\ModuleManager::getInstance()->module("translation");
            $module = \pinion\modules\ModuleManager::getInstance()->module($value);
            return $translationModule->translateBackend($module->information["title"]);
        };
        
        foreach($resources as &$resource) {
            $resource = $resource->attributes(array(
                "*",
                "module" => $translate
            ));
        }
        
        $this->response
            ->setInfo("resources", $resources)
            ->addMessage($this, $this->translate("resources of rule %s updated", "<b>".$this->translate($rule->name)."</b>"));
    }
    
    public function changeResourcesOfRule(Event $event) {
        
        $resources = $event->getInfo("resources") ?: array();
        
        foreach($resources as $resource) {
            $id = $resource["rule"];
            $data = explode("_", $resource["id"]);
                
            // find resource
            $resourceData = $this->data->find_by_module_and_permission_and_rule_id("resource", $data[0], $data[1], $id);
            
            if($resource["active"]) {
                if(!is_null($resourceData)) {
                    $resourceData->allow = $resource["allow"];
                    $resourceData->save();
                } else {
                    $this->data->create("resource", array(
                        "module" => $data[0],
                        "permission" => $data[1],
                        "allow" => $resource["allow"],
                        "rule_id" => $id
                    ));
                }
            } else {
                if($resourceData) {
                    $resourceData->delete();
                }
            }
        }
        
        $this->response
            ->addSuccess($this, $this->translate("resources of rule %s updated", "<b>".$this->data->find("rule", $id)->name."</b>"));
    }
    
    public function getResources() {
        return array_merge(parent::getResources(), array(
            "add user",
            "add rule",
            "edit rule",
            "delete rule",
            "edit resources of rule"
        ));
    }
    
    public function usernameIsUnique(Event $event) {
        $userStorage = $this->data->getStorage("user");
        
        $username = $event->getInfo("username");
        
        $user = $userStorage::find_by_username($username);
        
        $this->response->setInfo("valid", $user ? "Username '$username' is already used" : true);
        
        return $user ? false : true;
    }

    public function addUser(Event $event) {

        $username = $event->getInfo("username");
        
        $userStorage = $this->data->getStorage("user");
        if($userStorage::find_by_username($username)) {
            $this->response->addError($this, "Username '$username' is already in use!");
            return;
        }

        $this->data->create("user", array(
            "username" => $username,
            "password" => $this->generatePassword($event->getInfo("password")),
            "firstname" => $event->getInfo("firstname"),
            "lastname" => $event->getInfo("lastname"),
            "rule_id" => $event->getInfo("rule"),
            "email" => $event->getInfo("email"),
            "language" => $event->getInfo("language", "en"),
            "shortcuts" => "[]",
            "lastupdate" => 0,
            "hasmessages" => false
        ));

        $this->response->addSuccess($this, "User '$username' was successfully added");
    }
    
    public function changeUser(Event $event) {
        
        $user = $this->data->find("user", $this->identity->userid);
        
        $firstname = $event->getInfo("firstname");
        $lastname = $event->getInfo("lastname");
        $username = $event->getInfo("username");
        $password = $event->getInfo("password");
        $email = $event->getInfo("email");
        $language = $event->getInfo("language");
        $image = $event->getInfo("image");
        
        if($firstname) {
            $this->identity->firstname = $firstname;
            $user->firstname = $firstname;
            $this->response->addInfo("eval", "jQuery('#pinion-backend-user-firstname').text('$firstname')");
        }
        if($lastname) {
            $this->identity->lastname = $lastname;
            $user->lastname = $lastname;
            $this->response->addInfo("eval", "jQuery('#pinion-backend-user-lastname').text('$lastname')");
        }
        if($username) {
            $this->identity->username = $username;
            $user->username = $username;
        }
        if($password) {
            $user->password = sha1($password);
        }
        if($email) {
            $this->identity->email = $email;
            $user->email = $email;
        }
        if($language) {
            $this->identity->language = $language;
            $user->language = $language;
        }
        if($image) {
            $user->image = base64_encode($this->module("image")->load($image)->resizeAndCrop(50, 50)->getLoadedImage()->asString("jpg"));
        }
        $user->save();
        
        $this->response
            ->addSuccess($this, $this->translate("user account updated"));
    }
    
    public function changePositionsOfRules(Event $event) {
        
        $data = $event->getInfo("data");
        
        foreach($data as $ruleData) {
            $rule = $this->data->find("rule", $ruleData["id"]);
            
            $rule->rule_id = $ruleData["rule_id"];
            $rule->position = $ruleData["position"];
            $rule->name = $ruleData["name"];
            $rule->save();
        }
        
        $this->response->addSuccess($this, $this->translate("order of rules updated"));
    }

    private function generatePassword($password) {
        return \sha1($password.$this->data->getSalt());
    }
    
    public function changeRuleOfUser(Event $event) {
        $user = $this->data->find("user", $event->getInfo("id"));
        $user->rule_id = $event->getInfo("rule_id");
        $user->save();
        
        $this->response
            ->addSuccess($this, $this->translate("rule of user %s updated", "<b>".$user->username."</b>"));
    }
    
    public function updateShortcuts(Event $event) {
        $user = $this->data->find("user", $this->identity->userid);
        $user->shortcuts = $event->getInfo("shortcuts");
        $user->save();
        
        $this->identity->shortcuts = $user->shortcuts;
        
        $this->response
            ->addSuccess(null, $this->translate("shortcuts updated"));
    }
    
    public function defineBackend() {
        
        $cleanDate = function($value) {
            return date("d.m.Y", $value);
        };
        
        $resourcesPool = array();
        
        $usableModules = self::$moduleManager->getUsableModules();
        foreach($usableModules as $module) {
            $resources = $module->getResources();
            foreach($resources as $resource) {
                $resourcesPool[] = array(
                    "id" => $module->name."_".$resource,
                    "module" => $module->information["title"],
                    "permission" => $resource,
                    "allow" => null
                );
            }
        }
        
        $userData = array();
        
        $users = $this->data->all("user");
        foreach($users as $user) {
            $attrs = $user->attributes();
            $attrs["rule"] = $user->rule->name;
            $attrs["userPic"] = file_exists(MODULES_PATH."image/files/user_{$user->id}.png") ? MODULES_URL."/image/files/user_{$user->id}.png" : SITE_URL."pinion/assets/images/icons/defaultUserPic.png";
            $parent = $user->parent;
            $attrs["user"] = $parent ? $parent->username : "";
            $userData[] = $attrs;
        }
        
        $rulesWithoutAdministrators = $this->data->all("rule", array("conditions" => array("name != ?", "Administrators")));
        
        $rules = array();
        $ruleResources = array();
        foreach($rulesWithoutAdministrators as $rule) {
            $name = $rule->name;
            $rules[$name] = $rule->id;
            $ruleResources[$name] = array();
            $resources = $rule->resources;
            $resourcesArray = array();
            foreach($resources as $resource) {
                $resourcesArray[$resource->module."_".$resource->permission] = array(
                    "allow" => $resource->allow,
                    "created" => $resource->created,
                    "updated" => $resource->updated
                );
            }
            foreach($resourcesPool as $resource) {
                $ruleResource = $resource;
                if(isset($resourcesArray[$resource["id"]])) {
                    $ruleResource["allow"] = $resourcesArray[$resource["id"]]["allow"];
                    $ruleResource["created"] = $resourcesArray[$resource["id"]]["created"];
                    $ruleResource["updated"] = $resourcesArray[$resource["id"]]["updated"];
                }
                $module = $ruleResource["module"];
                if(!isset($ruleResources[$name][$module])) {
                    $ruleResources[$name][$module] = array();
                }
                $ruleResources[$name][$module][] = $ruleResource;
            }
        }
        
        $languageData = array();
        $languages = $this->module("translation")->languages;
        foreach($languages as $index => $language) {
            $languageData[] = array(
                "id" => $index,
                "name" => $language
            );
        }
        
        // USER ACCOUNT TAB
        $this->framework
            ->startGroup("LazyMainTab", array("title" => "User account", "groupEvents" => true))
                ->html("SimpleHtml", array("html" => "You can change your data here. Please click on the elements you want to update, change the data and submit it by pushing <i>enter</i>."))
                ->input("UpdateTextbox", array(
                    "label" => "first name",
                    "value" => $this->identity->firstname,
                    "infoKey" => "firstname",
                    "events" => array(
                        "event" => "changeUser" 
                    ),
                    "validators" => array("notEmpty" => true),
                    "help" => "write your firstname"
                ))
                ->input("UpdateTextbox", array(
                    "label" => "last name",
                    "value" => $this->identity->lastname,
                    "infoKey" => "lastname",
                    "events" => array(
                        "event" => "changeUser" 
                    ),
                    "validators" => array("notEmpty" => true),
                    "help" => "write your lastname"
                ))
                ->input("UpdateTextbox", array(
                    "label" => "email",
                    "value" => $this->identity->email,
                    "infoKey" => "email",
                    "events" => array(
                        "event" => "changeUser" 
                    ),
                    "validators" => array(
                        "notEmpty" => true,
                        "email" => true
                    ),
                    "help" => "write your email-address"
                ))
                ->input("UpdateTextbox", array(
                    "label" => "username",
                    "value" => $this->identity->username,
                    "infoKey" => "username",
                    "events" => array(
                        "event" => "changeUser" 
                    ),
                    "validators" => array(
                        "notEmpty" => true,
                        "events" => array(
                            "event" => "usernameIsUnique"
                        )
                    ),
                    "help" => "write your username"
                ))
                ->input("UpdateTextbox", array(
                    "identifier" => "updatePassword",
                    "label" => "password",
                    "infoKey" => "password",
                    "password" => true,
                    "events" => array(
                        "event" => "changeUser"
                    ),
                    "validators" => array(
                        "notEmpty" => true,
                        "minChars" => 5
                    ),
                    "help" => "write your password"
                ))
                ->input("UpdateTextbox", array(
                    "label" => "confirm password",
                    "password" => true,
                    "validators" => array("notEmpty" => true, "sameAs" => "updatePassword"),
                    "dirty" => "never",
                    "help" => "confirm your password"
                ))
                ->input("SimpleImageUploader", array(
                    "label" => "image",
                    "infoKey" => "image",
                    "events" => array(
                        "event" => "changeUser" 
                    ),
                    "help" => "upload an image"
                ))
                ->list("UpdateSelector", array(
                    "label" => "language",
                    "value" => $this->identity->language,
                    "noEmptyValue" => true,
                    "infoKey" => "language",
                    "data" => $languageData,
                    "events" => array(
                        "event" => "changeUser" 
                    ),
                    "validators" => array("notEmpty" => true),
                    "help" => "choose your language"
                ));
       
       if($this->hasPermission("add user")) {
           // ADD USER TAB         
           $this->framework  
                ->startGroup("LazyMainTab", array("title" => "Add user", "validate" => "all", "groupEvents" => true))
                    ->html("SimpleHtml", array("html" => "You can add a user here. Please fill out the forms and select <b>one</b> rule."))
                    ->input("Textbox", array(
                        "label" => "firstname",
                        "events" => array(
                            "event" => "addUser" 
                        ),
                        "validators" => array("notEmpty" => true),
                        "help" => "write a firstname"
                    ))
                    ->input("Textbox", array(
                        "label" => "lastname",
                        "events" => array(
                            "event" => "addUser"
                        ),
                        "validators" => array("notEmpty" => true),
                        "help" => "write a lastname"
                    ))
                    ->input("Textbox", array(
                        "label" => "email",
                        "infoKey" => "email",
                        "events" => array(
                            "event" => "addUser" 
                        ),
                        "validators" => array(
                            "notEmpty" => true,
                            "email" => true
                        ),
                        "help" => "write an email-address"
                    ))
                    ->input("Textbox", array(
                        "label" => "username",
                        "infoKey" => "username",
                        "events" => array(
                            "event" => "addUser" 
                        ),
                        "validators" => array(
                            "notEmpty" => true,
                            "events" => array(
                                "event" => "usernameIsUnique"
                            )
                        ),
                        "help" => "write an username"
                    ))
                    ->input("Textbox", array(
                        "identifier" => "password",
                        "label" => "password",
                        "infoKey" => "password",
                        "password" => true,
                        "events" => array(
                            "event" => "addUser" 
                        ),
                        "validators" => array(
                            "notEmpty" => true,
                            "minChars" => 5
                        ),
                        "help" => "write an password"
                    ))
                    ->input("Textbox", array(
                        "identifier" => "confirmPassword",
                        "label" => "confirm password",
                        "password" => true,
                        "validators" => array("notEmpty" => true, "sameAs" => "password"),
                        "dirty" => "never",
                        "help" => "confirm the password"
                    ))
                    ->list("Selector", array(
                        "label" => "language",
                        "infoKey" => "language",
                        "data" => $languageData,
                        "value" => "en",
                        "events" => array(
                            "event" => "addUser" 
                        ),
                        "noEmptyValue" => true,
                        "validators" => array("notEmpty" => true),
                        "help" => "choose a language"
                    ))
                    ->list("Finder", array(
                        "label" => "rule",
                        "selectable" => true,
                        "validators" => array("oneSelected" => true),
                        "recursive" => "rule_id",
                        "infoKey" => "rule",
                        "data" => $this->data->getAttributes("rule", array(
                            "id",
                            "rule_id",
                            "name",
                            "created" => $cleanDate
                        )),
                        "events" => array(
                            "event" => "addUser" 
                        ),
                        "help" => "choose a rule"
                    ));
       } 
       
        
       
       
        // USER TAB
        $this->framework   
            ->startGroup("LazyMainTab", array("title" => "User"))
                ->html("SimpleHtml", array("html" => "You can see lists of all rules and users here. For changing the rule of a user please drag the rule into the field of the user.<br />Also you can see, if a user is online (green online-sign) and you can send a mesasage to a user by clicking the massage-button on the right side of the user."))
                ->collection("FinderDragLeftToRight", array(
                    0 => array(
                        "splitting" => "1,2"
                    ),
                        1 => array(
                            "data" => $this->data->getAttributes("rule", array(
                                "id",
                                "name",
                                "created" => $cleanDate
                            ))
                        ),
                        2 => array(
                            "data" => $userData,
                            "renderer" => array(
                                "name" => "UserRenderer",
                                "events" => array(
                                    "event" => "changeRuleOfUser"
                                )
                            )
                        )
                ));
        
        
       
        // RULES TAB
        $editRulesAllowed = $this->hasPermission("edit rule");
        $this->framework       
            ->startGroup("LazyMainTab", array("title" => "Rules"))
                ->startGroup("TitledGroup", array("title" => "Edit", "open" => true))
                    ->html("SimpleHtml", array("html" => "You can change the name of a rule here. Please click on the rule-name you want to update, change the data and submit it by pushing <i>enter</i>."))
                    ->list("Finder", array(
                        "recursive" => "rule_id",
                        "renderer" => "RuleRenderer",
                        "multiple" => false,
                        "selectable" => false,
                        "draggable" => $editRulesAllowed,
                        "data" => $this->data->getAttributes($rulesWithoutAdministrators, array(
                            "*",
                            "user" => function($value) {
                                if($value) {
                                    return $value->username;
                                }
                            }
                        )),
                        "events" => $editRulesAllowed ? array(
                            "event" => "changePositionsOfRules" 
                        ) : array()
                    ))
                    ->end();
                        
                        
        if($this->hasPermission("add rule")) {
            $this->framework
                ->startGroup("TitledGroup", array("title" => "Add", "groupEvents" => true, "validate" => "all", "open" => false))
                    ->input("Textbox", array(
                        "label" => "name",
                        "events" => array(
                            "event" => "addRule" 
                        ),
                        "validators" => array("notEmpty" => true),
                    ))
                    ->list("Finder", array(
                        "label" => "parent",
                        "selectable" => false,
                        "recursive" => "rule_id",
                        "data" => $this->data->getAttributes("rule"),
                        "renderer" => array(
                            "name" => "SimpleRuleRenderer",
                            "oneSelected" => true,
                            "events" => array(
                                "event" => "addRule"
                            )
                        ),
                        "groupEvents" => true
                    ))
                    ->end();
        }
        
        
        if($this->hasPermission("delete rule")) {    
            $this->framework
                ->startGroup("TitledGroup", array("title" => "Delete", "open" => false))
                    ->list("Finder", array(
                        "selectable" => false,
                        "recursive" => "rule_id",
                        "data" => $this->data->getAttributes($rulesWithoutAdministrators),
                        "renderer" => array(
                            "name" => "SimpleRuleRenderer",
                            "multipleSelected" => true,
                            "events" => array(
                                "event" => "removeRule"
                            )
                        ),
                        "groupEvents" => "rules"
                    ));
        }
            
        
        if($this->hasPermission("edit resources of rule")) {
            // RESOURCES TAB
            $this->framework 
                ->startGroup("LazyMainTab", array("title" => "Resources"))
                    ->html("SimpleHtml", array("html" => "Please click on a rule to see it's resources. You can drag resources from the finder on the right side to the finder on the left at the bottom."))
                        ->startGroup("ColumnGroup", array("groupEvents" => true, "splitting" => "1,2"))
                            ->startGroup("TitledSection", array("title" => "Rules", "groupEvents" => true))
                                ->list("Finder", array(
                                    "groupEvents" => true,
                                    "identifier" => "rulesFinder",
                                    "renderer" => "SimpleRuleRenderer",
                                    "multiple" => false,
                                    "selectable" => false,
                                    "draggable" => false,
                                    "infoKey" => "rule",
                                    "data" => $this->data->getAttributes($rulesWithoutAdministrators, array(
                                        "id",
                                        "name",
                                        "rule_id",
                                        "created",
                                        "updated"
                                    )),
                                    "recursive" => "rule_id",
                                    "events" => array(
                                        "event" => "changeResourcesOfRule"
                                    )
                                ))
                                ->end()
                            ->startGroup("TitledSection", array("title" => "Resources"))
                                ->startGroup("LazyTabGroup");

                                foreach($ruleResources as $name => $modules) {
                                    $this->framework
                                        ->startGroup("TitledSection", array("title" => $name))
                                            ->startGroup("LazySelectGroup", array("label" => "Modules"));
                                    foreach($modules as $moduleName => $resourceData) {
                                        $this->framework
                                            ->startGroup("TitledSection", array(
                                                "title" => $moduleName
                                            ))
                                                ->permissions("ResourcesFinder", array(
                                                    "groupEvents" => "resources",
                                                    "identifier" => "resourcesPoolFinder",
                                                    "selectable" => false,
                                                    "data" => $resourceData,
                                                    "renderer" => array(
                                                        "name" => "ResourceRenderer",
                                                        "events" => array(
                                                            "event" => "changeResourcesOfRule",
                                                            "info" => array("rule" => $rules[$name])
                                                        )
                                                    )
                                                ))
                                                ->end();
                                    }
                                    $this->framework
                                        ->end() // TitledSection
                                        ->end(); // LazySelectGroup
                                }

                            $this->framework
                                    ->end()
                                ->end();
        }
        
                
        
    }
    
    public function menu() {
        return array(
            "user->User account",
            "user->Add user",
            "user->Manage permissions" => "Resources"
        );
    }
}
?>
