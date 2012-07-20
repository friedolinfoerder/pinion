<?php
/**
 * The FrontController searchs for the requested site and will show the correct
 * site to the user.
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

use \pinion\modules\ModuleManager;
use \pinion\data\models\Page_page as Page;

class FrontController {
    
    /**
     * The ModuleManager
     * 
     * @var ModuleManager $_moduleManager 
     */
    private $_moduleManager;

    /**
     * The constructor of FrontController
     * 
     * @param ModuleManager $moduleManager The ModuleManager
     */
    public function __construct(ModuleManager $moduleManager) {
        $this->_moduleManager = $moduleManager;
    }

    /**
     * Show a page
     * 
     * @param Request   $request  The request object
     * @param Response  $response The response object
     * @param Session   $session  The session object
     * @param \stdClass $identity The identity object
     * 
     * @return null 
     */
    public function showPage(Request $request, Response $response, Session $session, $identity) {
        $page = null;
        $pageData = $this->_moduleManager->module("page")->data;
        
        // add backend, if there is a get parameter login or the user is logged in
        $login = $request->getGetParameter("login");
        $preview = $request->getGetParameter("preview");
        if($identity || (isset($login) && !isset($preview))) {
            $response->write($this->_moduleManager->module("backend"));
        }
        
        $language = $request->getGetParameter("lang");
        if($language) {
            $session->setParameter("lang", $language);
        } elseif(!isset($_SESSION["lang"])) {
            $session->setParameter("lang", Registry::getLanguage());
        }
        
        if(Registry::inMaintenanceMode()) {
            $this->_moduleManager->module("page")->pageMaintenance();
            return;
        }
        
        $pagestr = $request->getGetParameter("page", "");
        
        if(\is_numeric($pagestr)) {
            $page = $pageData->find("page", $pagestr);
        } else {
            $page = $pageData->find_by_url("page", $pagestr);
        }
        
        if($page) {
            $response->setPage($page);
            $content = $response->getPageContent();
            
            if(!is_null($content) && ($content->visible || $identity)) {
                
                // if no module stops the writing of the page, write the page
                if($response->dispatchEvent("writePage", array("page" => $page)) !== false) {
                    $response->write($content);
                }
                
                if($identity) {
                    $currentBackendPage = $session->getParameter("currentBackendPage");
                    if($currentBackendPage) {
                        $response->addJsCode("jQuery(function() {pinion.page('$currentBackendPage'); });");
                    }
                } else {
                    $pageData->create("visit", array(
                        "page_id" => $page->id
                    ));
                }
            } else {
                $this->_moduleManager->module("page")->page404();
            }
        } else {
            $this->_moduleManager->module("page")->page404();
        }
    }
}
?>
