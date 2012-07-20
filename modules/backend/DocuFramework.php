<?php

/**
 * Description of DocuFramework
 * 
 * PHP version 5.3
 * 
 * @category   ???
 * @package    pinion
 * @subpackage ???
 * @author     Friedolin Förder <friedolinfoerder@pinion-cms.de>
 * @license    license placeholder
 * @version    SVN: ???
 * @link       http://www.pinion-cms.de
 */
namespace modules\backend;

use \pinion\transfer\Framework as TransferFramework;

/**
 * Description of DocuFramework
 * 
 * @category   data
 * @package    pinion
 * @subpackage database
 * @author     Friedolin Förder <friedolinfoerder@pinion-cms.de>
 * @license    license placeholder
 * @link       http://www.pinion-cms.de
 */
class DocuFramework {
    
    private $_framework;
    protected $_currentType;
    protected $_currentName;
    protected $_newElement;
    
    public function __construct(TransferFramework $framework) {
        $this->_framework = $framework;
    }
    
    public function __call($name, $arguments) {
        
        // add a test event to every element
        if(isset($arguments[1])) {
            $arguments[1] = array_merge(array(
                "events" => array(
                    "event" => "testEvent"
                )
            ), $arguments[1]);
        } else {
            $arguments[1] = array(
                "events" => array(
                    "event" => "testEvent"
                )
            );
        }
        
        
        call_user_func_array(array($this->_framework, $name), $arguments);
        
        // fluent interface
        return $this;
    }
    
    public function startTypeDocu($type) {
        
        $type = strtolower($type);
        $headline = $type;
        
        if(substr($type, 0, 5) == "start") {
            $headline = substr($type, 5);
        }
        
        $this->_framework
            ->startGroup("LazyTitledGroup", array("title" => $headline));
        
        $this->_currentType = $type;
        
        // fluent interface
        return $this;
    }
    
    public function startElementDocu($name) {
        
        $this->_framework
            ->startGroup("LazyTitledGroup", array("title" => $name))
                ->startGroup("Accordion");
        
        $this->_newElement = true;
        $this->_currentName = $name;
        
        // fluent interface
        return $this;
    }
    
    public function endElementDocu() {
        $this->_framework
            ->end()
            ->end();
        
        // fluent interface
        return $this;
    }
    
    public function example($headline, array $options = array()) {
        
        $this
            ->startExample($headline, $options)
            ->endExample();
        
        // fluent interface
        return $this;
    }
    
    public function startExample($headline, array $options = array()) {
        
        $type = $this->_currentType;
        $name = $this->_currentName;
        
        $this->_framework
            ->startGroup("LazyTitledGroup", array("title" => $headline))
                ->$type($name, $this->saveArray($options));
        
        // fluent interface
        return $this;
    }
    
    public function endExample() {
        
        $type = $this->_currentType;
        
        if(substr($type, 0, 5) == "start") {
            $this->_framework->end();
        }
        
        $this->_framework
            ->startGroup("LazyTitledGroup", array("title" => "source", "open" => false))
                ->text("Code", array("array" => $this->getLastArray()))
                ->end()
            ->end();
        
        // fluent interface
        return $this;
    }
    
    protected function saveArray($array) {
        $this->_backendArray = $array;
        return $array;
    }
    
    protected function getLastArray() {
        return $this->_backendArray;
    }
}

?>
