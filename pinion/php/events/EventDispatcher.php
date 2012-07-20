<?php
/**
 * This is the default implementation of the interface IEventDispatcher, so you can 
 * add and remove EventListener and dispatch events.
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


class EventDispatcher implements IEventDispatcher {
    
    /**
     * An array with EventListener
     * 
     * @var array $listener 
     */
    private $listener = array();

    /**
     * Checks if an EventListener exists
     * 
     * @param string         $eventname           The name of the event
     * @param Closure|string $closureOrMethodname The function to call after the event. Either an anonymous function or a name of a method.
     * 
     * @return boolean True if the EventListener exists, false otherwise
     */
    public function hasEventListener($eventname, $closureOrMethodname = null) {
        if(empty($this->listener[$eventname])) return false;
        
        // if there is only one argument, the eventname and the functionname are the same
        if($closureOrMethodname == null) {
            $closureOrMethodname = $eventname;
        }
        
        if($closureOrMethodname instanceof \Closure) {
            foreach($this->listener[$eventname] as $listener) {
                if($closureOrMethodname === $listener) {
                    return true;
                }
            }
            return false;
        } else if(func_num_args() > 2) {
            foreach($this->listener[$eventname] as $listener) {
                if(!is_array($listener)) continue;
                
                if($listener["method"] === $closureOrMethodname && $listener["class"] === func_get_arg(2)) {
                    return true;
                }
            }
            return false;
        } else {
            foreach($this->listener[$eventname] as $listener) {
                if(!is_array($listener)) continue;
                
                if($listener["method"] === $closureOrMethodname && $listener["class"] === $this) {
                    return true;
                }
            }
            return false;
        }
    }
    
    /**
     * Register a function or method for a event with the given name of the event.
     * 
     * @param string         $eventname           The name of the event.
     * @param Closure|string $closureOrMethodname The function to call after the event. Either an anonymous function or a name of a method.
     * @param Object         $handler             The object, which got the function, which was given in argument before.
     * 
     * @return EventDispatcher Fluent interface: Return this.
     */
    public function addEventListener($eventname, $closureOrMethodname = null, $handler = null) {
        if(!isset($this->listener[$eventname])) {
            $this->listener[$eventname] = array();
        }
        
        // if there is no $closureOrMethodname argument, the eventname 
        // and the functionname are the same
        if($closureOrMethodname == null) {
            $closureOrMethodname = $eventname;
        }
        
        // if there is a closure, add the closure to the listeners, otherwise
        // add an array with the handler and the method string to the listeners
        if($closureOrMethodname instanceof \Closure) {
            $this->listener[$eventname][] = $closureOrMethodname;
        } else {
            $this->listener[$eventname][] = array(
                "class"  => is_object($handler) ? $handler : $this,
                "method" => $closureOrMethodname
            );
        }
        
        // fluent interface
        return $this;
    }
    
    /**
     * Unregister a function or method for a event with the given name of the event.
     * 
     * @param string         $eventname           The name of the event.
     * @param Closure|string $closureOrMethodname The function to call after the event. Either an anonymous function or a name of a method.
     * @param Object         $handler             The object, which got the function, which was given in argument before.
     * 
     * @return EventDispatcher Fluent interface: Return this.
     */
    public function removeEventListener($eventname, $closureOrMethodname) {
        
        if(!isset($this->listener[$eventname])) {
            // fluent interface
            return $this;
        }
            
        
        $listenerList = $this->listener;
        foreach($listenerList[$eventname] as $index => $listener) {
            if($closureOrMethodname instanceof \Closure) {
                if($listener instanceof \Closure && $listener == $closureOrMethodname)
                    unset($this->listener[$eventname][$index]);
            } else if(func_num_args() > 2) {
                if(is_array($listener) && $listener["method"] == $closureOrMethodname && $listener["class"] == func_get_arg(2))
                    unset($this->listener[$eventname][$index]);
            } else {
                if(is_array($listener) && $listener["method"] == $closureOrMethodname && $listener["class"] == $this)
                    unset($this->listener[$eventname][$index]);
            }
        }
        
        
        // fluent interface
        return $this;
    }

    /**
     * Dispatch an event
     * 
     * @param  Event       $event
     * @param  null|array  $info
     * @param  null|string $context
     * 
     * @return boolean true if the process should go on, false otherwise 
     */
    public function dispatchEvent($event, $info = null, $context = null) {
        if(!$event instanceof Event) {
            if($info == null) {
                $info = array();
            }
            $info["sender"] = $this;
            $event = new Event($event, $info, $context);
        }

        $eventname = $event->getName();
        if(!isset($this->listener[$eventname])) {
            return true;
        }
        
        $break = false;
        $result = true;
        foreach($this->listener[$eventname] as $listenerinfo) {
            if($listenerinfo instanceof \Closure) {
                if($listenerinfo($event) === false) {
                    $break = true;
                }
            } else {
                $listener = $listenerinfo["class"];
                $method = $listenerinfo["method"];

                $result = $listener->$method($event);
                if($result === false) {
                    $break = true;
                }
            }
            
        }
        if($break) {
            return false;
        } else {
            return $result;
        }
    }
}
?>
