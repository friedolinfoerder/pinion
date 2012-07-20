<?php
/**
 * The class Authorizator manages the whole authorization.
 * Here you can get or delete the current identity.
 * 
 * PHP version 5.3
 * 
 * @category   authorization
 * @package    authorization
 * @subpackage authorization
 * @author     Friedolin Förder <friedolin.foerder@pinion-cms.org>
 * @license    MIT License
 * @link       http://www.pinion-cms.org
 */


namespace pinion\authorization;

use \Zend_Auth;
use \pinion\data\models\Permissions_user as User;

class Authorizator {

    /**
     * Get the identity of the user
     * 
     * @param \pinion\general\Request $request
     * @param  string                 $salt
     * 
     * @return null|Object null if the user is not logged in, otherwise an identity object 
     */
    public function getIdentity($request, $salt) {
        if($request->hasGetParameter("preview")) {
            return null;
        }
        
        $auth = Zend_Auth::getInstance();

        if($auth->hasIdentity()) {
            $identity = $auth->getIdentity();
            
            return $identity; // <- Rule in Tabelle aufnehmen
        } else {
            $username = $request->getPostParameter("username");
            $password = $request->getPostParameter("password");

            return $this->getIdentityWithLogin($username, $password.$salt);
        }
    }

    /**
     * Clear the identity of the user 
     */
    public function clearIdentity() {
        Zend_Auth::getInstance()->clearIdentity();
    }

    /**
     * Check, if the user has an identity
     * 
     * @return boolean Checks, if the user has an identity 
     */
    public function hasIdentity() {
        return Zend_Auth::getInstance()->hasIdentity();
    }

    /**
     * Checks, if the user can log in with username and password
     * 
     * @param string $username The username of the user
     * @param string $password The password of the user
     * @return null|Object null, if the user is not logged in, otherwise an identity object 
     */
    protected function getIdentityWithLogin($username, $password) {
        if(empty($username) || empty($password)) return null;

        $authAdapter = new AuthAdapter($username, $password);

        $auth = Zend_Auth::getInstance();

        $result = $auth->authenticate($authAdapter);

        if($result->isValid()) {
            $storage = $auth->getStorage();

            $user = User::find_by_username($username);
            $resources = array();
            
            $rule = $user->rule;
            $ruleIds = array();
            if($rule->name == "Administrators") {
                // set resources to null and get the resources later
                $resources = null;
            } else {
                while($rule) {
                    $ruleIds[] = (int) $rule->id;

                    $newResources = $rule->resources;

                    foreach($newResources as $resource) {
                        if(!isset($resources[$resource->module])) {
                            $resources[$resource->module] = array();
                        }
                        if(!isset($resources[$resource->module][$resource->permission])) {
                            $resources[$resource->module][$resource->permission] = $resource->allow;
                        }
                    }

                    $rule = $rule->parent;
                }
                
                // clean resources (remove resources which are not allowed)
                foreach($resources as $moduleName => $module) {
                    foreach($module as $permission => $allowed) {
                        if(!$allowed) {
                            unset($resources[$moduleName][$permission]);
                        }
                    }
                    if(empty($resources[$moduleName])) {
                        unset($resources[$moduleName]);
                    }
                }
            }
            
            
            
            
            $userinfo = array(
                "username" => $username,
                "userid" => (int) $user->id,
                "rule" => $user->rule->name,
                "ruleids" => array_reverse($ruleIds),
                "permissions" => $resources,
                "firstname" => $user->firstname,
                "lastname" => $user->lastname,
                "email" => $user->email,
                "language" => $user->language,
                "shortcuts" => $user->shortcuts
            );

            $userinfo = (object)$userinfo;
            
            $storage->write($userinfo);

            // return rule
            return $auth->getIdentity();
        } else {
            return null;
        }
    }

}
?>