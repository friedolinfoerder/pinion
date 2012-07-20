<?php
/**
 * Every module has an instance of this class.
 * With this class you can configure elements of the cms framework.
 * 
 * PHP version 5.3
 * 
 * @category   transfer
 * @package    transfer
 * @subpackage transfer
 * @author     Friedolin Förder <friedolin.foerder@pinion-cms.org>
 * @license    MIT License
 * @link       http://www.pinion-cms.org
 */


namespace pinion\transfer;

use \pinion\general\Response;
use \pinion\general\Session;
use \pinion\modules\Module;
use \pinion\modules\ModuleManager;
use \pinion\util\Object;


class Framework {
    
    /**
     * The current module
     * 
     * @var Module $_module
     */
    protected $_module;
    
    /**
     * The response object
     * 
     * @var Response $_response
     */
    protected $_response;
    
    /**
     * The session object
     * 
     * @var Session $_session
     */
    protected $_session;
    
    /**
     * The name of the module
     * 
     * @var string $_name
     */
    protected $_name;
    
    /**
     * The key, which is used for the communication object
     *
     * @var string $_key 
     */
    protected $_key = "backend";

    /**
     * The parent stack manages the nesting of the elements
     * 
     * @var array $_parentStack
     */
    protected $_parentStack = array();
    
    /**
     * The lazy stack manages the lazy loading functionality
     * 
     * @var array $_lazyStack
     */
    protected $_lazyStack = array();
    
    /**
     * All available collections
     * 
     * @var array $collections 
     */
    protected static $collections = array();

    /**
     * The constructor of Framework
     * 
     * @param Module   $module   The module, which belongs to this object
     * @param Response $response The response object
     * @param Session  $session  The session object
     */
    public function __construct(Module $module, Response $response, Session $session) {
        $this->_module = $module;
        $this->_response = $response;
        $this->_session = $session;
    }
    
    /**
     * Add a set of elements as a collection to the collection array
     * 
     * @param string $name      The name of the collection
     * @param array  $structure The elements
     * 
     * @return \pinion\transfer\Framework Returns this
     */
    public function addCollection($name, array $structure) {
        self::$collections[$name] = $structure;
        
        return $this;
    }
    
    /**
     * Build the options for an element
     * 
     * @param string $type    The type of the element
     * @param string $name    The name of the element
     * @param array  $options The options of the element
     * 
     * @return \pinion\transfer\Framework Returns this
     */
    public function display($type, $name, array $options = array()) {
        $options["type"] = $type;
        $options["name"] = $name;
        
        // every array with key events should have the same structure, so it's easier for the js elements
        $this->rewriteEvents($options);
        
        $newTab = false;
        if($type == "group" && substr($name, -7) == "MainTab") {
            $this->_parentStack = array();
            $this->_lazyStack = array();
            $newTab = true;
        }
        
        $last = end($this->_parentStack);
        if($last) {
            $options["parent"] = $last;
        }
        
        $last = end($this->_lazyStack);
        if($last && !isset($options["lazy"])) {
            $options["lazy"] = $last;
        }
        
        if($this->_name === null) {
            $this->_name = $this->_module->name;
        }
        
        if($this->_key == "backend") {
            $this->_response
                ->setInfo("backend.name", $this->_name)
                ->addInfo("backend.elements", $options);
        
            $this->_session->setParameter("currentBackendPage", "pinion/modules/".$this->_module->name);
        } else {
            $this->_response->addInfo($this->_key, $options);
        }
        
        
        // fluent interface
        return $this;
    }
    
    /**
     * Rewrite the options of an element
     * 
     * @param array $options The options of element
     */
    protected function rewriteEvents(array &$options) {
        foreach($options as $key => &$option) {
            if($key === "events") {
                if(!isset($option[0])) {
                    $option = array($option);
                }
                foreach($option as &$event) {
                    $this->completeEvent($event);
                }
            } elseif(($key === "data" || $key === "fire") && is_array($option) && isset($option["event"])) {
                $this->completeEvent($option);
            } elseif(is_array($option)) {
                // call recursive
                $this->rewriteEvents($option);
            }
        }
    }
    
    /**
     * Complete an event array
     * 
     * @param array $event The event array
     */
    protected function completeEvent(&$event) {
        // if there is no module set, set the module to the current module
        if(!isset($event["module"])) {
            $event["module"] = $this->_module->name;
        }
        // if there is no info set, add a empty object to the info
        if(!isset($event["info"])) {
            $event["info"] = new \stdClass();
        }
    }
    
    /**
     * magic method: Build an element
     * 
     * @param string $name      The type of the element
     * @param array  $arguments The name and the options of an element
     * 
     * @return \pinion\transfer\Framework Returns this
     */
    public function __call($name, $arguments) {
        
        if(substr($name, 0, 3) === "end") {
            // fluent interface
            return $this->end();
        }
        
        $startContext = false;
        $name = strtolower($name);
        
        // start new context if the name starts with 'start'
        if(substr($name, 0, 5) === "start") {
            $name = substr($name, 5);
            $startContext = true;
        }
        
        // set identifier
        if(!isset($arguments[1]["identifier"])) {
            $arguments[1]["identifier"] = $name.".".$arguments[0]."_".uniqid();
        }
        
        array_unshift($arguments, $name);
        call_user_func_array(array($this, "display"), $arguments);
        
        if($startContext) {
            $identifier = $arguments[2]["identifier"];
            
            $this->_parentStack[] = $identifier;
            
            if(substr($arguments[1], 0, 4) == "Lazy") {
                $this->_lazyStack[] = $identifier;
            }
        }
        
        if($name == "group" && substr($arguments[1], -7) == "MainTab") {
            // if there is a new tab
            // add a headline to this tab
            $this->text("Headline", array("text" => $arguments[2]["title"]));
        }
        
        // fluent interface
        return $this;
    }
    
    /**
     * End the current nesting context
     * 
     * @return \pinion\transfer\Framework Returns this
     */
    public function end() {
        $identifier = array_pop($this->_parentStack);
        
        if(end($this->_lazyStack) == $identifier) {
            array_pop($this->_lazyStack);
        }
        
        // fluent interface
        return $this;
    }
    
    /**
     * Build a existing collection
     * 
     * @param string|array $nameOrStructure A collection name or a array which contains elements
     * @param array        $structure       An array which contains elements
     * 
     * @return \pinion\transfer\Framework Returns this
     */
    public function collection($nameOrStructure, array $structure = array()) {
        $collectionStructure;
        if(is_string($nameOrStructure)) {
            if(isset(self::$collections[$nameOrStructure])) {
                $collectionStructure = self::$collections[$nameOrStructure];
            } else {
                return;
            }
        } elseif(is_array($nameOrStructure)) {
            $collectionStructure = $nameOrStructure;
        } else {
            return;
        }
            
        $mergedStructure = Object::merge($collectionStructure, $structure);
        
        for($i = 0, $length = count($mergedStructure); $i < $length; $i++) {
            $element = $mergedStructure[$i];

            if(!isset($element["name"])) {
                $element["name"] = null;
            }

            $this->{$element["type"]}($element["name"], $element);
        }
        
        return $this;
    }
    
    /**
     * Set the name of the module
     * 
     * @param string $name The name of the module
     * 
     * @return \pinion\transfer\Framework Returns this
     */
    public function name($name) {
        $this->_name = $name;
        
        $this->_response->setInfo("backend.name", $this->_name);
        
        // fluent interface
        return $this;
    }
    
    /**
     * Set the key
     * 
     * @param string $name The attribute name of the communication object
     * 
     * @return \pinion\transfer\Framework Returns this
     */
    public function key($name) {
        $this->_key = $name;
        
        // fluent interface
        return $this;
    }
}

?>
