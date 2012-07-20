<?php

/**
 * Description of LanguageSwitcher
 * 
 * PHP version 5.3
 * 
 * @category   modules
 * @package    pinion
 * @subpackage renderer
 * @author     Friedolin Förder <friedolinfoerder@pinion-cms.de>
 * @license    license placeholder
 * @version    SVN: ???
 * @link       http://www.pinion-cms.org
 */
namespace modules\page;

use \pinion\modules\Renderer;
use \pinion\general\Request;
/**
 * Description of LanguageSwitcher
 * 
 * @category   modules
 * @package    pinion
 * @subpackage renderer
 * @author     Friedolin Förder <friedolinfoerder@pinion-cms.de>
 * @license    license placeholder
 * @link       http://www.pinion-cms.org
 */
class PageCreator implements Renderer {
    
    public $name = "page";
    public $template = "page-404";
    
    /**
     *
     * @var string $_pageUrl The relative url of the page
     */
    protected $_pageUrl;
    
    /**
     *
     * @param string $url
     * @param type $type 
     */
    public function __construct($url, $type) {
        $this->_pageUrl = $url;
        
        $this->template = "page-$type";
        if($type == "404") {
            header("HTTP/1.0 404 Not Found");
        }
    }
    
    public function setFrontendVars($data) {
        return array(
            "url" => $_SERVER['SERVER_NAME'].SITE_URL,
            "page" => $this->_pageUrl
        );
    }
}

?>