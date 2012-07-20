<?php

/**
 * Description of LanguageSwitcher
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
namespace modules\translation;

use \pinion\modules\Renderer;
use \pinion\general\Registry;
/**
 * Description of LanguageSwitcher
 * 
 * @category   data
 * @package    pinion
 * @subpackage database
 * @author     Friedolin Förder <friedolinfoerder@pinion-cms.de>
 * @license    license placeholder
 * @link       http://www.pinion-cms.de
 */
class LanguageSwitcher implements Renderer {
    
    public $name = "translation";
    public $template = "languageSwitcher";
    
    public function setFrontendVars($data) {
        return array(
            "languages" => Registry::getSupportedLanguages(),
            "active" => $_SESSION["lang"]
        );
    }
}

?>
