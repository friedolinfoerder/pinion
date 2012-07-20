<?php
/**
 * If you implement this interface, you can add and remove EventListener and
 * dispatch events.
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


interface IEventDispatcher {
    
    /**
     * Register a function or method for a event with the given name of the event.
     * 
     * @param string         $eventname           The name of the event.
     * @param Closure|string $closureOrMethodname The function to call after the event. Either an anonymous function or a name of a method.
     * @param Object         $handler             The object, which got the function, which was given in argument before.
     * 
     * @return IEventDispatcher Fluent interface: Return this.
     */
    public function addEventListener($eventName, $closureOrMethodname = null, $handler = null);
    
    /**
     * Unregister a function or method for a event with the given name of the event.
     * 
     * @param string         $eventname           The name of the event.
     * @param Closure|string $closureOrMethodname The function to call after the event. Either an anonymous function or a name of a method.
     * @param Object         $handler             The object, which got the function, which was given in argument before.
     * 
     * @return IEventDispatcher Fluent interface: Return this.
     */
    public function removeEventListener($eventName, $closureOrMethodname);
    
    /**
     * Dispatch an event
     * 
     * @param  Event       $event
     * @param  null|array  $info
     * @param  null|string $context
     * 
     * @return boolean true if the process should go on, false otherwise 
     */
    public function dispatchEvent($eventName, $info = null, $context = null);
}
?>
