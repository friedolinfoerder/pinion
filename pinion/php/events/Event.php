<?php
/**
 * This event can be thrown from classes, which implement the IEventDispatcher
 * interface. It has methods to get the name, the information and the context
 * of the current event.
 * 
 * PHP version 5.3
 * 
 * @category   events
 * @package    events
 * @subpackage events
 * @author     Friedolin Förder <friedolin.foerder@pinion-cms.org>
 * @license    MIT License
 * @link       http://www.pinion-cms.org
 */


namespace pinion\events;

class Event {
    
    /**
     * The name of the event
     * 
     * @var string $_name 
     */
    private $_name;
    
    /**
     * The current context
     * 
     * @var Object $_context
     */
    private $_context;
    
    /**
     * An array with info about the event
     * 
     * @var array $_info 
     */
    private $_info;
    
    /**
     * This can be set to true via a getter to cancel the current event dispatching
     * 
     * @var boolean $_cancelled 
     */
    private $_cancelled = false;

    /**
     * The constructor of Event
     * 
     * @param string $name    The name of the event
     * @param array  $info    An array with additional attributes for the event
     * @param Object $context The current context, in which the event should be thrown
     */
    public function __construct($name, array $info = array(), $context = null) {
        $this->_name = $name;
        $this->_context = $context;
        $this->_info = $info;
    }

    /**
     * Get the name of the event
     * 
     * @return string The name of the event 
     */
    public function getName() {
        return $this->_name;
    }

    /**
     * Get the attributes of the event
     * 
     * @param string $key     The key of an attribute
     * @param mixed  $default An default value
     * 
     * @return mixed An array of attributes or one specific attribute 
     */
    public function getInfo($key = null, $default = null) {
        if($key == null) {
            $info = $this->_info;
            $info["sender"] = "{$info["sender"]}";
            return $info;
        }
            
        if(array_key_exists($key, $this->_info)) {
            return $this->_info[$key];
        } else {
            return $default;
        }
    }
    
    /**
     * Checks if an attribute exists in the event attributes
     * 
     * @param string $key The attribute name
     * 
     * @return boolean True if the attribute exists, false otherwise 
     */
    public function hasInfo($key) {
        return array_key_exists($key, $this->_info);
    }
    
    /**
     * Get the context
     * 
     * @return Object The current context
     */
    public function getContext() {
        return $this->_context;
    }

    /**
     * Checks if it is cancelled
     * 
     * @return boolean True if it is cancelled, false otherwise 
     */
    public function isCancelled() {
        return $this->_cancelled;
    }

    /**
     * Cancel the current event dispatching 
     */
    public function cancel() {
        $this->_cancelled = true;
    }
}
?>
