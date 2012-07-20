<?php
/**
 * This is an abstraction of the superglobals $_GET, $_POST and $_REQUEST.
 * 
 * PHP version 5.3
 * 
 * @category   general
 * @package    general
 * @subpackage general
 * @author     Friedolin Förder <friedolin.foerder@pinion-cms.org>
 * @license    MIT License
 * @link       http://www.pinion-cms.org
 */


namespace pinion\general;

class Request {

    /**
     * The superglobal $_REQUEST 
     * 
     * @var array $_request
     */
    private $_request;
    
    /**
     * The superglobal $_POST 
     * 
     * @var array $_post
     */
    private $_post;
    
    /**
     * The superglobal $_GET 
     * 
     * @var array $_get
     */
    private $_get;
    
    /**
     * The superglobal $_SERVER['REQUEST_METHOD']
     * 
     * @var string $_method 
     */
    private $_method;

    /**
     * The constructor of Request 
     */
    public function __construct() {
        $this->_request = $_REQUEST;
        $this->_post = $_POST;
        $this->_get = $_GET;
        
        $this->_method = $_SERVER['REQUEST_METHOD'];
        
        // delete superglobal variables
        $_REQUEST = $_POST = $_GET = array();
    }
    
    /**
     * Checks if the request parameter exists
     * 
     * @param string $name The name of the parameter
     * 
     * @return boolean true, if the request parameter exists, false otherwise 
     */
    public function hasRequestParameter($name) {
        $args = func_get_args();
        foreach($args as $arg) {
            if(!isset($this->_request[$arg])) {
                return false;
            }
        }
        return true;
    }
    
     /**
     * Checks if the get parameter exists
     * 
     * @param string $name The name of the parameter
      * 
     * @return boolean true, if the get parameter exists, false otherwise 
     */
    public function hasGetParameter($name) {
        $args = func_get_args();
        foreach($args as $arg) {
            if(!isset($this->_get[$arg])) {
                return false;
            }
        }
        return true;
    }
    
     /**
     * Checks if the post parameter exists
     * 
     * @param string $name The name of the parameter
      * 
     * @return boolean true, if the post parameter exists, false otherwise 
     */
    public function hasPostParameter($name) {
        $args = func_get_args();
        foreach($args as $arg) {
            if(!isset($this->_post[$arg])) {
                return false;
            }
        }
        return true;
    }

    /**
     * You get every parameter of the request, regardless of which (get or post)
     * 
     * @param string $name    The name of the parameter
     * @param mixed  $default The default value
     * 
     * @return mixed The parameter returns. If there is none, the default value will return.
     */
    public function getRequestParameter($name, $default = null) {
        if($this->hasRequestParameter($name)) {
            return $this->_request[$name];
        }
        return $default;
    }

    /**
     * You get the post parameter with the given name.
     * 
     * @param string $name    The name of the parameter
     * @param mixed  $default The default value
     * 
     * @return mixed 
     */
    public function getPostParameter($name, $default = null) {
        if($this->hasPostParameter($name)) {
            return $this->_post[$name];
        }
        return $default;
    }

    /**
     * You get the get parameter with the given name.
     * 
     * @param string $name    The name of the parameter
     * @param mixed  $default The default value
     * 
     * @return mixed 
     */
    public function getGetParameter($name, $default = null) {
        if($this->hasGetParameter($name)) {
            return $this->_get[$name];
        }
        return $default;
    }
    
    /**
     * Magic method: You can get a request parameter, when you require a
     * (not available) attribute of this class
     * 
     * @param string $name The name of the parameter
     * 
     * @return mixed 
     */
    public function __get($name) {
        return $this->getRequestParameter($name);
    }
    
    /**
     * Checks if the request is an ajax request
     * 
     * @return boolean Return true if it's a ajax request, false otherwise 
     */
    public function isAjax() {
        return (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && ($_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest'));
    }
    
    /**
     * Get the request method
     * 
     * @return string The request method 
     */
    public function getMethod() {
        return $this->_method;
    }
    
    /**
     * Get the request array
     * 
     * @return array The superglobal $_REQUEST 
     */
    public function getRequest() {
        return $this->_request;
    }
    
    /**
     * Get the post array
     * 
     * @return array The superglobal $_POST 
     */
    public function getPost() {
        return $this->_post;
    }
    
    /**
     * Get the get array
     * 
     * @return array The superglobal $_GET 
     */
    public function getGet() {
        return $this->_get;
    }
}
?>
