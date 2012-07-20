<?php
/**
 * The class AuthAdapter will be used from the Authorizator
 * The class checks, if an user is authorized to see the backend.
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

use \Zend_Auth_Adapter_Interface;
use \Zend_Auth_Result;
use \pinion\data\models\Permissions_user as User;

class AuthAdapter implements Zend_Auth_Adapter_Interface {

    /**
     * The username of the user
     * 
     * @var string $_username 
     */
    protected $_username;
    
    /**
     * The password of the user
     * 
     * @var string $_password 
     */
    protected $_password;

    /**
     * Set the username and the password in the constructor
     * 
     * @param string $username The username of the user
     * @param string $password The password of the user
     */
    public function __construct($username, $password) {
        $this->_username = $username;
        $this->_password = sha1($password);
    }

    /**
     * Check, if the user can reach the backend of the cms
     * 
     * @return \Zend_Auth_Result The result of the check
     */
    public function authenticate() {

        $user = User::find_by_username($this->_username);

        if(!isset($user)) {
            return new Zend_Auth_Result(Zend_Auth_Result::FAILURE_IDENTITY_NOT_FOUND,$this->_username);
        }

        if($user->password != $this->_password) {
            return new Zend_Auth_Result(Zend_Auth_Result::FAILURE_CREDENTIAL_INVALID,$this->_username);
        }

        if($user->password == $this->_password) {
            return new Zend_Auth_Result(Zend_Auth_Result::SUCCESS,$this->_username);
        }

        return new Zend_Auth_Result(Zend_Auth_Result::FAILURE_UNCATEGORIZED,$this->_username);
    }
}
?>
