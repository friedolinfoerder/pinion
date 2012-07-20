<?php
/**
 * Class Renderer
 * 
 * PHP version 5.3
 * 
 * @category   modules
 * @package    modules
 * @subpackage modules
 * @author     Friedolin Förder <friedolin.foerder@pinion-cms.org>
 * @license    MIT License
 * @link       http://www.pinion-cms.org
 */
/**
 * If you want to have an class, which can be rendered on a page, you must
 * implement this interface.
**/

namespace pinion\modules;


interface Renderer {
    
    /**
     * Set all template variables
     * 
     * @param $data The current data of the module
     * 
     * @return array All properties which are used to render the object 
     */
    public function setFrontendVars($data);
}

?>
