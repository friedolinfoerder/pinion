<?php
/**
 * If the user is logged in, the TemplatePostProcessor adds classes to the
 * outer tag of the template, so the javascript framework can find the cms
 * elements.
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

class TemplatePostProcessor {
    
    /**
     * Add the cms classes to the template
     * 
     * @param string $originalContent The html of the template
     * @param string $classes         The classes to add
     * 
     * @return \pinion\general\TemplatePostProcessor Returns this instance
     */
    public function addCmsClasses(&$originalContent, $classes) {
        // find first html tag
        $pattern = "/<[^\?]((<\?(.*?)\?>)|.)*?>/";
        preg_match($pattern, $originalContent, $simpleResults, PREG_OFFSET_CAPTURE);
        $firstHtmlTagContent = $simpleResults[0][0];
        $firstHtmlTagPosition = $simpleResults[0][1];
        
        // if first tag has class attribute, write a additional class, and if not, write the class attribute with the class
        if(preg_match("/class\s*=s*[\"'](.*?)[\"']/i", $firstHtmlTagContent, $results, PREG_OFFSET_CAPTURE)) {
            $classNames = $results[1][0];
            $classPosition = $results[1][1];
            $originalContent = substr_replace($originalContent, "$classes ", $firstHtmlTagPosition + $classPosition, 0);
        } else {
            $originalContent = substr_replace($originalContent, " class='$classes'", $firstHtmlTagPosition + strlen($firstHtmlTagContent) - 1, 0);
        }
        
        // fluent interface
        return $this;
    }
    
}

?>
