<?php
/**
 * Module Cache
 * 
 * PHP version 5.3
 * 
 * @author  Friedolin Förder <friedolin.foerder@pinion-cms.org>
 * @license MIT License
 * @link    http://www.pinion-cms.org/module/cache
 */

namespace modules\cache;

use \pinion\modules\Module;
use \pinion\modules\Renderer;
use \pinion\events\Event;
use \pinion\modules\ModuleManager;
use \pinion\data\database\ActiveRecord;
use \pinion\files\DirectoryRearranger;

class Cache extends Module {
    
    protected $_mode;
    protected $_jsFile;
    protected $_cssFile;
    protected $_cache;
    protected $_hasPrinted = false;
    
    public function install() {
        $this->data
            ->createDataStorage("cache", array(
                "html" => array("type" => "text", "isNull" => true, "translatable" => false),
                "lang" => array("type" => "varchar", "length" => 100, "translatable" => false),
                "page" => "page"
            ), array(
                "revisions" => false
            ));
        
        $this->data->options("newCache", true, true);
    }
    
    public function addListener() {
        parent::addListener();
        
        // set mode
        $this->_mode = $this->identity ? "backend" : "frontend";
        
        if(!$this->identity && !$this->request->hasGetParameter("login") && !$this->request->isAjax()) {
            // $this->response->addEventListener("writePage", "writePage", $this);
            $this->response->addEventListener("flush", "compressFiles", $this);
            // $this->response->addEventListener("print", "printPage", $this);
        } else {
            if(!$this->request->isAjax()) {
                $this->response->addEventListener("flush", "compressFiles", $this);
            }
            
            // $this->addEventListener("clearCache");
        }
        // ActiveRecord::addEventListener("change", "clearPageCache", $this);
    }
    
    public function clearCache(Event $event) {
        $this->data->table("cache")->delete(array());
        
        $this->response->addSuccess($this, $this->translate("The cache was cleared"));
    }
    
    public function init() {
        // get Minify
        require_once "minify_2.1.3/min/lib/Minify.php";
        require_once "minify_2.1.3/min/lib/JSMinPlus.php";
    }
    
    public function clearPageCache(Event $event) {
        $data = $this->data->find_by_page_id("cache", $this->session->getParameter("page"));
        if(is_object($data)) {
            $data->delete();
        }
    }
    
    public function writePage(Event $event) {
        $cache = $this->data->find_by_page_id_and_lang("cache", $this->session->getParameter("page"), $this->session->getParameter("lang"));
        if(is_null($cache)) {
            ActiveRecord::$noChange = true;
            $this->_cache = $this->data->create("cache", array(
                "lang" => $this->session->getParameter("lang"),
                "page_id" => $this->session->getParameter("page")
            ));
            ActiveRecord::$noChange = false;
        } elseif(is_null($cache->html)) {
            $this->_cache = $cache;
        } else {
            // print out the content
            $this->_hasPrinted = true;
            print $cache->html;
            return false;
        }
    }
    
    public function printPage(Event $event) {
        if(is_object($this->_cache)) {
            require_once "minify_2.1.3/min/lib/Minify/HTML.php";
            $this->_cache->html = \Minify_HTML::minify($event->getInfo("page"));
            $this->_cache->save(true, true);
        } elseif($this->_hasPrinted) {
            // The page has already be printed in the writePage-Event-Handler
            return false;
        }
    }
    
    public function compressFiles(Event $event) {
        // if there is no cache object, the content was already printed
        if(!$this->identity && is_null($this->_cache)) return;
        
        // set include path
        set_include_path(MODULES_PATH.$this->name."/minify_2.1.3/min/lib/" . PATH_SEPARATOR . get_include_path());
        
        // get time
        $time = time();
        
        // js files
        $this->_combineJsFiles($time);
        
        // css files
        $this->_combineCssFiles($time);
    }
    
    protected function _deleteFiles($type) {
        if($this->_mode == "backend") {
            $dirIterator = new \DirectoryIterator(MODULES_PATH.$this->name."/files/$type/{$this->_mode}");
            foreach($dirIterator as $file) {
                if(substr($file->getFilename(), -strlen($type)-1) == ".$type") {
                    \unlink($file->getPath()."/".$file->getFilename());
                }
            }
        } else {
            $path = MODULES_PATH.$this->name."/files/$type/{$this->_mode}/{$this->session->getParameter("page")}";
            if(file_exists($path)) {
                DirectoryRearranger::remove($path);
            }
        }
    }
    
    protected function _combineCssFiles($time) {
        
        $this->_deleteFiles("css");
        if($this->_mode == "backend") {
            $pathEnd = "";
        } else {
            $pathEnd = $this->session->getParameter("page")."/";
        }
        
        $files = $this->response->getCssFiles();
        
        $fileName = "$time.css";
        $relativePath = $this->name."/files/css/{$this->_mode}/$pathEnd";
        if($this->_mode != "backend") {
            mkdir(MODULES_PATH.$relativePath);
        }
        $filePath = MODULES_PATH.$relativePath.$fileName;
        $fileUrl = MODULES_URL."/".$relativePath.$fileName;
        
        // get css data
        $data = \Minify::combine(array_values($files), array("currentDir" => MODULES_PATH.$relativePath)).";";
        
        // write file
        file_put_contents($filePath, $data);
        
        $this->response->setCssFiles(array($fileUrl => $filePath));
    }
    
    protected function _combineJsFiles($time) {
        
        
        $this->_deleteFiles("js");
        if($this->_mode == "backend") {
            $pathEnd = "";
        } else {
            $pathEnd = $this->session->getParameter("page")."/";
        }
        
        $files = $this->response->getJsFiles();
        
        $fileName = "$time.js";
        $relativePath = $this->name."/files/js/{$this->_mode}/$pathEnd";
        if($this->_mode != "backend") {
            mkdir(MODULES_PATH.$relativePath);
        }
        $filePath = MODULES_PATH.$relativePath.$fileName;
        $fileUrl = MODULES_URL."/".$relativePath.$fileName;
        
        // create data
        $data = \JSMinPlus::minify($this->response->getJsVarsString()).";";
        
//        $data .= \Minify::combine(array_values($files));
        foreach($files as $file) {
            // append content of file
            $data .= file_get_contents($file).";";
        }
        $data .= \JSMinPlus::minify($this->response->getJsCodeString());
        
        // write file
        file_put_contents($filePath, $data);
        
        $this->response
            ->setJsVarsString("")
            ->setJsCodeString("")
            ->setJsFiles(array($fileUrl => $filePath));
    }
    
    public function useFiles(Event $event) {
        
        $jsFile = $this->_jsFile;
        $cssFile = $this->_cssFile;
        
        // get time
        $time = time();
        
        $this->response
            ->setJsVarsString("")
            ->setJsCodeString("")
            ->setJsFiles(array(MODULES_URL."/".$jsFile => MODULES_PATH.$jsFile));
        $this->response->setCssFiles(array(MODULES_URL."/".$cssFile => MODULES_PATH.$cssFile));
    }
    
    protected function _findFile($type) {
        $path = $this->name."/files/$type/{$this->_mode}/";
        
        $dirIterator = new \DirectoryIterator(MODULES_PATH.$path);
        $filename = null;
        foreach($dirIterator as $file) {
            $filename = $file->getFilename();
            if($file->isFile() && $filename{0} != ".") {
                $varName = "_{$type}File";
                $this->{$varName} = $path.$filename;
                return true;
            }
        }
        return false;
    }
    
    public function defineBackend() {
        parent::defineBackend();
        
        $this->framework
            ->startGroup("LazyMainTab", array("title" => "Clear cache"))
                ->input("Switcher", array(
                    "label" => "clear cache",
                    "events" => array(
                        "event" => "clearCache"
                    )
                ));
    }
    
    public function menu() {
        return array("Clear cache");
    }
}

?>
