<?php
/**
 * This is an abstraction of the superglobal $_SESSION and $_COOKIE.
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

class Session {
    
    /**
     * The cookies which should be automatically updated
     * 
     * @var array $_cookiesForUpdateMapping
     */
    protected $_cookiesForUpdateMapping = array();
    
    /**
     * Constructor of Session 
     */
    public function __construct() {
        // cookie for 30 days
        setcookie("cookiesAllowed", "true", \time() + 60*60*24*30);
        
        
        // update cookies with autoupdate
        foreach($_COOKIE as $name => $value) {
            $matches = array();
            if(preg_match("/^_cookieForUpdate_(\d*)_(.*)$/", $name, $matches)) {
                $this->_cookiesForUpdateMapping[$matches[2]] = $name;
                $this->setParameter($name, $value, $matches[1]);
            }
        }
    }
    
    /**
     * Checks if there is a parameter with the given name
     * 
     * @param string $name The parameter name
     * 
     * @return boolean True if the parameter exists, false otherwise
     */
    public function hasParameter($name) {
        if(isset($this->_cookiesForUpdateMapping[$name])) {
            $name = $this->_cookiesForUpdateMapping[$name];
        }
        if(isset($_COOKIE[$name])) {
            return true;
        } 
        if(isset($_SESSION[$name])) {
            return true;
        }
        return false;
    }

    /**
     * Get a session parameter
     * 
     * @param string $name    The name of the get parameter
     * @param mixed  $default The default value
     * 
     * @return mixed The value of the parameter 
     */
    public function getParameter($name, $default = null) {
        
        if(isset($this->_cookiesForUpdateMapping[$name])) {
            $name = $this->_cookiesForUpdateMapping[$name];
        }
        if(isset($_COOKIE[$name])) {
            if(isset($_SESSION[$name])) {
                unset($_SESSION[$name]);
            }
            return $_COOKIE[$name];
        } 
        if(isset($_SESSION[$name])) {
            return $_SESSION[$name];
        }
        
        return $default;
    }

    /**
     * Set a session parameter
     * 
     * @param string  $name          The name of the parameter
     * @param mixed   $value         The value of the parameter
     * @param int     $expireTillNow The time till the cookie expires (in seconds)
     * @param boolean $autoUpdate    Set to true if the cookie lifetime should be updated in every request
     */
    public function setParameter($name, $value, $expireTillNow = null, $autoUpdate = false) {
        if($expireTillNow) {
            $name = $autoUpdate ? "_cookieForUpdate_{$expireTillNow}_$name" : $name; 
            setcookie($name, $value, \time()+$expireTillNow);
        }
        if(!$expireTillNow || !isset($_COOKIE["cookiesAllowed"])) {
            $_SESSION[$name] = $value;
        }
    }
    
    /**
     * Unset a session parameter
     * 
     * @param string $name The name of the parameter
     * 
     * @return \pinion\general\Session The session object
     */
    public function unsetParameter($name) {
        if(isset($_COOKIE[$name])) {
            unset($_COOKIE[$name]);
        }
        if(isset($_SESSION[$name])) {
            unset($_SESSION[$name]);
        }
        
        // fluent interface
        return $this;
    }
}

?>
