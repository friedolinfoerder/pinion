<?php
/**
 * This Response handles the page output. It handles the whole html output, which
 * includes the head with javascript and stylesheet files, meta tags, the title and
 * the body with all visible module elements. If there is an ajax request, it will
 * also handle the json output string and therefore here are function for adding
 * success, warning and error messages.
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

use \pinion\modules\Module;
use \pinion\modules\Renderer;
use \pinion\general\TemplateBuilder;
use \pinion\modules\ModuleManager;
use \pinion\data\models\Page_page as Page;
use \pinion\data\models\Page_content as Content;
use \pinion\events\EventDispatcher;
use \pinion\data\database\ActiveRecord;
use \pinion\general\Session;

class Response extends EventDispatcher {
    
    /**
     * The html5 doctype identifier
     * 
     * @var string $DOCTYPE_HTML5
     */
    public static $DOCTYPE_HTML5 = "html5";
    
    /**
     * The html4 strict doctype identifier
     * 
     * @var string $DOCTYPE_HTML4_STRICT
     */
    public static $DOCTYPE_HTML4_STRICT = "html4strict";

    /**
     * The ActiveRecord class of the current page
     * 
     * @var \pinion\data\models\Page_page $_page 
     */
    protected $_page;
    
    /**
     * The Page_content class of the current page 
     * 
     * @var array $_pageContent
     */
    protected $_pageContent;
    
    /**
     * An array with Page_content classes of the current page 
     * 
     * @var array $_contents
     */
    protected $_contents;
    
    /**
     * The title of the page
     * 
     * @var string $_title
     */
    protected $_title;
    
    /**
     * The title prefix for all pages
     * 
     * @var string $_titlePrefix 
     */
    protected $_titlePrefix;
    
    /**
     * The url of the current page
     * 
     * @var string $_url
     */
    protected $_url;
    
    /**
     * The template of the current page
     * 
     * @var string $_template 
     */
    protected $_template;
    
    /**
     * The basic structure of the html
     * 
     * @var string $_structure
     */
    protected $_structure;
    
    /**
     * An array with infos, which will be sent on an ajax request
     * 
     * @var array $_infos
     */
    protected $_infos = array();
    
    /**
     * An array with unique messages
     * 
     * @var array $_uniqueMessages
     */
    protected $_uniqueMessages = array();

    /**
     * The content in the body
     * 
     * @var string $_data 
     */
    protected $_data = "";
    
    /**
     * An array with javascript variables
     * 
     * @var array $_jsVars 
     */
    protected $_jsVars = array();
    
    /**
     * A string with javascript variables
     * 
     * @var string $_jsVarsString 
     */
    protected $_jsVarsString;
    
    /**
     * An array with javascript code blocks
     * 
     * @var array $_jsCode 
     */
    protected $_jsCode = array();
    
    /**
     * A string with javascript code
     * 
     * @var string $_jsCodeString 
     */
    protected $_jsCodeString;
    
    /**
     * An array with meta tag information
     * 
     * @var array $_meta 
     */
    protected $_meta = array();
    
    /**
     * An array with javascript- and stylesheet-files of the backend
     * 
     * @var array $_backendFiles 
     */
    protected $_backendFiles = array(
        "css" => array(),
        "js"  => array()
    );
    
    /**
     * An array with javascript- and stylesheet-files of the frontend
     * 
     * @var array $_files 
     */
    protected $_files = array(
        "css" => array(),
        "js"  => array()
    );
    
    /**
     * A counter, which counts how many templates are active at the same time
     * 
     * @var int $_activeTemplateCount
     */
    protected $_activeTemplateCount = 0;
    
    /**
     * The identity of the user
     * 
     * @var null|\stdClass $_identity 
     */
    protected $_identity;
    
    /**
     * The PostProcessor
     * 
     * @var PostProcessor $postProcessor 
     */
    protected $_postProcessor;
    
    /**
     * The ModuleManager
     * 
     * @var ModuleManager $moduleManager
     */
    protected $_moduleManager;
    
    /**
     * The TemplateBuilder
     * 
     * @var TemplateBuilder $_templateBuilder 
     */
    protected $_templateBuilder;
    
    /**
     * The request object
     * 
     * @var Request $_request
     */
    protected $_request;
    
    /**
     * Flag to determine if the request is an ajax request
     * 
     * @var boolean $_isAjax 
     */
    protected $_isAjax;
    
    /**
     * The session object
     * 
     * @var Session $_session
     */
    protected $_session;
    
    /**
     * The constructor of Response
     * 
     * @param Request $request The request object
     * @param Session $session The session object
     */
    public function __construct(Request $request, Session $session) {
        $this->_request = $request;
        $this->_session = $session;
        $this->_templateBuilder = new TemplateBuilder($this, $session);
        
        $this->_isAjax = $request->isAjax();
    }
    
    /**
     * Set the ModuleManager
     * 
     * @param ModuleManager $moduleManager The ModuleManager
     * 
     * @return \pinion\general\Response The response object
     */
    public function setModuleManager(ModuleManager $moduleManager) {
        $this->_moduleManager = $moduleManager;
        
        // fluent interface
        return $this;
    }
    
    /**
     * Set the PostProcessor
     * 
     * @param TemplatePostProcessor $postProcessor The PostProcessor
     * 
     * @return \pinion\general\Response The response object
     */
    public function setPostProcessor(TemplatePostProcessor $postProcessor) {
        $this->_postProcessor = $postProcessor;
        
        // fluent interface
        return $this;
    }
    
    /**
     * Set the identity object
     * 
     * @param \stdClass $identity The identity object
     * 
     * @return \pinion\general\Response The response object
     */
    public function setIdentity($identity) {
        $this->_identity = $identity;
        
        // fluent interface
        return $this;
    }
    
    /**
     * Add info to the info array
     * 
     * @param string $path The path of the attribute
     * @param mixed  $info The value of the attribute
     * 
     * @example addInfo("debug", "My debug string")
     * @example addInfo("foo.bar.key", array("value")
     * 
     * @return \pinion\general\Response The response object 
    */
    public function addInfo($path, $info) {
        
        $keys = explode(".", $path);
        $parent = &$this->_infos;
        
        foreach($keys as $key) {
            if(!isset($parent[$key])) {
                $parent[$key] = array();
            }
            $parent = &$parent[$key];
        }
        $parent[] = $info;
        
        if($path == "backend") {
            $_SESSION[$path] = $this->_infos;
        }
        
        // fluent interface
        return $this;
    }
    
    /**
     * Set info in the info array
     * 
     * @param string $path The path of the attribute
     * @param mixed  $info The value of the attribute
     * 
     * @example addInfo("debug", "My debug string")
     * @example addInfo("foo.bar.key", array("value")
     * 
     * @return \pinion\general\Response The response object
     */
    public function setInfo($path, $info) {
        
        if($path == "") {
            $this->_infos = $info;
            return $this;
        }
        
        $keys = explode(".", $path);
        $parent = &$this->_infos;
        
        foreach($keys as $key) {
            if(!isset($parent[$key])) {
                $parent[$key] = array();
            }
            $parent = &$parent[$key];
        }
        $parent = $info;
        
        // fluent interface
        return $this;
    }
    
    /**
     * Get the complete info array or the info of an attribute path
     * 
     * @param string $path The path of the attribute
     * 
     * @return mixed The complete info array or the requested attribute 
     */
    public function getInfo($path) {
        
        if(empty($path)) {
            return $this->_infos;
        }
        
        $keys = explode(".", $path);
        $parent = &$this->_infos;
        
        foreach($keys as $key) {
            if(!isset($parent[$key])) {
                return null;
            }
            $parent = &$parent[$key];
        }
        
        return $parent;
    }
    
    /**
     * Set the prefix for the title
     * 
     * @param string $prefix The prefix for the title
     * 
     * @return \pinion\general\Response The response object
     */
    public function setTitlePrefix($prefix) {
        $this->_titlePrefix = $prefix;
        
        // fluent interface
        return $this;
    }
    
    /**
     * Set the current page
     * 
     * @param Page_page $page The current page
     * 
     * @return \pinion\general\Response The response object
     */
    public function setPage(Page $page) {
        // set page
        $this->_page = $page;
        
        // save the page id in the session
        $this->_session->setParameter("page", $page->id);
        
        // find and set page content
        $this->_pageContent = Content::find_by_module_and_moduleid("page", $page->id);
        if($this->_pageContent) {
            // set page and global contents
            $this->_contents = Content::all(array("conditions" => array("module != 'page' && (content_id IS NULL OR content_id = ?)", $this->_pageContent->id), "order" => "position ASC"));
            
            // save content id in the session
            $this->_session->setParameter("pageContent", $this->_pageContent->id);
        }
        // set page id as javascript variable
        $this->addJsVariable("pageid", $page->id);
        
        // set url
        $this->_url = $page->url;
        
        // set page title
        $this->_title = $page->title ?: str_replace("/", " - ", $page->url);
        
        // fluent interface
        return $this;
    }
    
    /**
     * Get the Page_content object of the current page
     * 
     * @return Page_content The ActiveRecord with the content of the page
     */
    public function getPageContent() {
        return $this->_pageContent;
    }
    
    /**
     * Get the current page
     * 
     * @return Page Returns the current page
     */
    public function getPage() {
        return $this->_page;
    }
    
    /**
     * Get the contents of the current page
     * 
     * @return array An array with contents of the current page 
     */
    public function getContents() {
        return $this->_contents;
    }
    
    /**
     * Add an value to the info array
     * 
     * @param null|Module $module    null or a module instance
     * @param mixed       $value     An value 
     * @param null|string $arrayname The name for the array, in which the value should be placed
     * @param boolean     $prepend   Set to true if the value should be prepended to the array
     * 
     * @return \pinion\general\Response The response object
     */
    protected function _addToInfoArray($module, $value, $arrayname = null, $prepend = false) {
        if($module == null) {
            // add the array to the info array if it not exists
            if(!isset($this->_infos[$arrayname])) {
                $this->_infos[$arrayname] = array();
            }
                
            if($prepend) {
                array_unshift($this->_infos[$arrayname], $value);
            } else {
                $this->_infos[$arrayname][] = $value;
            }
        } else {
            $module = $module->name;
            // add the module array to the info array if it not exists
            if(!isset($this->_infos[$module])) {
                $this->_infos[$module] = array();
            }
            
            // add the array to the module array if it not exists
            if(!isset($this->_infos[$module][$arrayname])) {
                $this->_infos[$module][$arrayname] = array();
            }
            
            if($prepend) {
                array_unshift($this->_infos[$module][$arrayname], $value);
            } else {
                $this->_infos[$module][$arrayname][] = $value;
            }
        }
        // fluent interface
        return $this;
    }
    
    /**
     * Add a message
     * 
     * @param string      $type    The type of the message
     * @param null|Module $module  null or a module instance
     * @param string      $message The text of the message
     * @param array       $options Options for the message
     */
    protected function _addMessage($type, $module, $message, array $options = array()) {
        
        // option for adding the same message again: "once" => false
        if(isset($options["once"]) && $options["once"] === true) {
            if(isset($this->_uniqueMessages[$message])) {
                return;
            } else {
                $this->_uniqueMessages[$message] = true;
            }
        }
        // option for prepend a message: "prepend" => true
        if(isset($options["prepend"]) && $options["prepend"] === true) {
            $prepend = true;
        } else {
            $prepend = false;
        }
        
        if($module instanceof Module) {
            $module = $module->name;
        }
        
        $array = array_merge(array(
            "type" => $type,
            "text" => $message,
            "revision" => Registry::getCurrentRevision()
        ), $options);
        
        if($module != null) {
            $array["module"] = $module;
        }
        
        $this->_addToInfoArray(null, $array, $type == "info" ? "infoMessages" : "messages", $prepend);
    }
    
    /**
     * Add a message
     * 
     * @param null|Module $module  null or a module instance
     * @param string      $message The text of the message
     * @param array       $options Options for the message
     * 
     * @return \pinion\general\Response The response object
     */
    public function addMessage($module, $message, array $options = array()) {
        $this->_addMessage("info", $module, $message, $options);
        
        // fluent interface
        return $this;
    }
    
    /**
     * Add a success message
     * 
     * @param null|Module $module  null or a module instance
     * @param string      $message The text of the message
     * @param array       $options Options for the message
     * 
     * @return \pinion\general\Response The response object
     */
    public function addSuccess($module, $message, array $options = array()) {
        $this->_addMessage("success", $module, $message, $options);
        
        // fluent interface
        return $this;
    }
    
    /**
     * Add a warning message
     * 
     * @param null|Module $module  null or a module instance
     * @param string      $message The text of the message
     * @param array       $options Options for the message
     * 
     * @return \pinion\general\Response The response object
     */
    public function addWarning($module, $message, array $options = array()) {
        $this->_addMessage("warning", $module, $message, $options);
        
        // fluent interface
        return $this;
    }
    
    /**
     * Add a error message
     * 
     * @param null|Module $module  null or a module instance
     * @param string      $message The text of the message
     * @param array       $options Options for the message
     * 
     * @return \pinion\general\Response The response object
     */
    public function addError($module, $message, array $options = array()) {
        $this->_addMessage("error", $module, $message, $options);
        
        // fluent interface
        return $this;
    }
    
    /**
     * Add a file information to the info array
     * 
     * @param string $type The type of the file (js- or css-File)
     * @param string $url  The url of the file
     * 
     * @return \pinion\general\Response The response object
     */
    protected function _addFileInfo($type, $url) {
        $this->_addToInfoArray(null, $url, $type);
        
        // fluent interface
        return $this;
    }
    
    /**
     * Print the infos as json string  
     */
    public function flushInfos() {
        $this->dispatchEvent("flushInfos");
        
        $files = array_merge(array_keys($this->getCssFiles()), array_keys($this->getJsFiles()));
        if(!empty($files)) {
            $this->_infos["files"] = $files;
        }
        
        if(isset($this->_infos["infoMessages"])) {
            if(!isset($this->_infos["messages"])) {
                $this->_infos["messages"] = array();
            }
            $this->_infos["messages"] = array_merge($this->_infos["infoMessages"],$this->_infos["messages"]);
            
            unset($this->_infos["infoMessages"]);
        }
        if(isset($this->_infos["messages"])) {
            $this->_infos["messages"] = array_reverse($this->_infos["messages"]);
        }
        
        print json_encode($this->_infos);
        $this->_infos = array();
    }
    
    /**
     * Get the content of the body
     * 
     * @return string The content of the body 
     */
    public function flushData() {
        $data = $this->_data;
        $this->_data = "";
        return $data;
    }

    /**
     * Print the page
     * 
     * @return \pinion\general\Response The response object
     */
    public function flush() {
        $this->dispatchEvent("flush", array(
            "data" => $this->_data
        ));
        
        if($this->_request->isAjax()) {
            return $this->flushData();
        }
        
        if(!isset($this->_structure)) {
            $this->setDoctype("html5");
        }
        
        $page = sprintf($this->_structure, $this->_generateHead()."<body>{$this->_data}</body>");
        if($this->dispatchEvent("print", array("page" => $page)) !== false) {
            print $page;
        }
        
        $this->_data = "";
        
        // fluent interface
        return $this;
    }
    
    /**
     * Get the javascript code as string
     * 
     * @return string The javascript code 
     */
    public function getJsCodeString() {
        if(is_string($this->_jsCodeString)) {
            return $this->_jsCodeString;
        } else {
            if(!empty($this->_jsCode)) {
                $script = "";
                foreach($this->_jsCode as $jsCode) {
                    $script .= "    $jsCode\n";
                }
                $this->_jsCodeString = $script;
            } else {
                $this->_jsCodeString = "";
            }
            
        }
        return $this->_jsCodeString;
        
    }
    
    /**
     * Set the javascript code
     * 
     * @param string $string The javascript code
     * 
     * @return \pinion\general\Response 
     */
    public function setJsCodeString($string) {
        if(is_string($string)) {
            $this->_jsCodeString = $string;
        }
        
        // fluent interface
        return $this;
    }
    
    /**
     * Get the javascript variables as string
     * 
     * @return string The javascript variables 
     */
    public function getJsVarsString() {
        if(is_string($this->_jsVarsString)) {
            return $this->_jsVarsString;
        } else {
            if(!empty($this->_jsVars)) {
                $script = "var pinion = {};\n\npinion.php = {\n";

                $jsVarsInObject = array();
                foreach($this->_jsVars as $jsVarKey => $jsVarValue) {
                    $jsVarsInObject[] = "    '$jsVarKey': ".$this->parseJsVariable($jsVarValue);
                }
                $script .= join(",\n", $jsVarsInObject);
                $script .= "\n};\n";
                
                $this->_jsVarsString = $script;
            } else {
                $this->_jsVarsString = "";
            }
            
        }
        return $this->_jsVarsString;
    }
    
    /**
     * Set the javascript variables
     * 
     * @param string $string The javascript variables
     * 
     * @return \pinion\general\Response The response object
     */
    public function setJsVarsString($string) {
        if(is_string($string)) {
            $this->_jsVarsString = $string;
        }
        
        // fluent interface
        return $this;
    }
    
    /**
     * Get all javascript files
     * 
     * @return array The frontend and backend javascript files 
     */
    public function getJsFiles() {
        return ($this->_files["js"] + $this->_backendFiles["js"]);
    }
    
    /**
     * Get all stylesheet files
     * 
     * @return array The frontend and backend stylesheet files 
     */
    public function getCssFiles() {
        return ($this->_files["css"] + $this->_backendFiles["css"]);
    }
    
    /**
     * Set the javascript files array
     * 
     * @param array $jsFiles An array with javascript files
     * 
     * @return \pinion\general\Response The response object
     */
    public function setJsFiles(array $jsFiles) {
        $this->_backendFiles["js"] = array();
        $this->_files["js"] = $jsFiles;
        
        // fluent interface
        return $this;
    }
    
    /**
     * Set the stylesheet files array
     * 
     * @param array $cssFiles An array with stylesheet files
     * 
     * @return \pinion\general\Response The response object
     */
    public function setCssFiles(array $cssFiles) {
        $this->_backendFiles["css"] = array();
        $this->_files["css"] = $cssFiles;

        // fluent interface
        return $this;
    }
    
    /**
     * Write a content
     * 
     * @param Content|ActiveRecord|string $data    The data to write the content with
     * @param string                      $classes optional An string with css classes
     * 
     * @return \pinion\general\Response The response object
     */
    public function write($data, $classes = null) {
        if($data instanceof Content) {
            $content = $data;
            if($this->_identity == null && !$this->_request->isAjax() && !$content->visible) {
                return $this; // don't show invisible contents, when you are not logged in
            }
            
            $module = $this->_moduleManager->module($data->module);
            if(is_null($module)) {
                return $this;
            }
            
            if($module instanceof Renderer) {
                $this->_activeTemplateCount++;
                $data = $this->_templateBuilder->renderTemplateOfRenderer("_main", $module, $content);
                $this->_activeTemplateCount--;
                
                if(is_null($classes)) {
                    $classes = "cms-{$content->id}";
                    if($this->_identity) {
                        $classes = "cms cms-content cms-{$content->id}-{$content->areaname}-$module-{$content->moduleid}";
                    }
                }
                if($classes != "") {
                    $this->_postProcessor->addCmsClasses($data, $classes);
                }
                
            }
            
        } elseif($data instanceof Renderer) {
            $this->_activeTemplateCount++;
            $data = $this->_templateBuilder->renderTemplateOfRenderer("_main", $data);
            $this->_activeTemplateCount--;
        }
        
        if($this->_activeTemplateCount == 0) {
            $this->_data .= $data;
        } else {
            print $data;
        }
        
        // fluent interface
        return $this;
    }

    /**
     * Add a stylesheet file
     * 
     * @param string      $url     An url of a stylesheet file or a directory
     * @param null|Object $context A context string
     * 
     * @return \pinion\general\Response The response object
     */
    public function addCss($url, $context = null) {
        if($this->_isAjax && array_key_exists($url.",".$context, $_SESSION["files"]["css"])) return $this;
        
        $_SESSION["files"]["css"][$url.",".$context] = true;
        $this->_addFile($url, $context, "css");
        
        // fluent interface
        return $this;
    }
    
    /**
     * Set an javascript variable
     * 
     * @param string  $key            The attribute name
     * @param mixed   $value          The attribute value
     * @param boolean $onlyIfLoggedIn Set to true if the attribute should only be set when logged in
     * 
     * @return \pinion\general\Response The response object
     */
    public function addJsVariable($key, $value, $onlyIfLoggedIn = false) {
        if($this->_isAjax || ($onlyIfLoggedIn && !$this->_identity)) return $this;
        
        $keys = explode(".", $key);
        $parent = &$this->_jsVars;
        
        foreach($keys as $key) {
            if(!isset($parent[$key])) {
                $parent[$key] = array();
            }
            $parent = &$parent[$key];
        }
        $parent = $value;
        
        // fluent interface
        return $this;
    }
    
    /**
     * Parse a javascript variable
     * 
     * @param mixed $var The variable, which should be parsed
     * 
     * @return string The parsed javascript variable 
     */
    protected function parseJsVariable($var) {
        if(is_int($var)) {
            return $var;
        } elseif(is_string($var)) {
            return "'$var'";
        } elseif(is_array($var) || is_object($var)) {
            return json_encode($var);
        }
    }
    
    /**
     * Add a javascript code block
     * 
     * @param string $code The javascript code
     * 
     * @return \pinion\general\Response The response object
     */
    public function addJsCode($code) {
        if($this->_isAjax) return $this;
        
        $this->_jsCode[] = $code;
        
        // fluent interface
        return $this;
    }
    
    /**
     * Add a meta information
     * 
     * @param string $tagContent A meta tag content
     * 
     * @return \pinion\general\Response The response object
     */
    public function addMeta($tagContent) {
        $this->meta[strtolower($tagContent)] = $tagContent;
        
        // fluent interface
        return $this;
    }

    /**
     * Add a javascript file
     * 
     * @param string      $url     An url of a javascript file or a directory
     * @param null|Object $context A context string
     * 
     * @return \pinion\general\Response The response object
     */
    public function addJs($url, $context = null) {
        if($this->_isAjax && array_key_exists($url.",".$context, $_SESSION["files"]["js"])) return $this;
        
        $_SESSION["files"]["js"][$url.",".$context] = true;
        $this->_addFile($url, $context, "js");
        
        // fluent interface
        return $this;
    }

    /**
     * Add a javascript or stylesheet file
     * 
     * @param string      $url     An url of an javasript or stylesheet file or a directory
     * @param null|Object $context A context string
     * @param string      $type    The type of the file
     * 
     * @return \pinion\general\Response The response object
     */
    private function _addFile($url, $context, $type) {
        if(!$this->dispatchEvent("addFile")) return $this;
        
        if(is_array($url)) {
            foreach($url as $u) {
                $this->_addFile($u, $context, $type);
            }
            return $this;
        }
        
        if($context == null) {
            $context = "";
        }
        $context = \strtolower($context);
        $inputContext = $context;
        $context = explode(":", $context);
        $inputurl = $url;
        $path;
        
        if(substr($url, 0, 7) == "http://") {
            if($context[0] == "backend") {
                $this->_backendFiles[$type][$url] = $url;
            } else {
                $this->_files[$type][$url] = $url;
            }
            
            return $this;
        }
        
        if($context[0] == 'template') {
            $path = TEMPLATES_PATH.Registry::getTemplate()."/{$context[1]}/$type/$url";
            $url =  TEMPLATES_URL."/".Registry::getTemplate()."/{$context[1]}/$type/$url";
        } elseif($context[0] == 'module') {
            $path = MODULES_PATH."{$context[1]}/templates/$type/$url";
            $url =  MODULES_URL."/{$context[1]}/templates/$type/$url";
        } elseif($context[0] == 'backend') {
            $path = MODULES_PATH."{$context[1]}/backend/$type/$url";
            $url =  MODULES_URL."/{$context[1]}/backend/$type/$url";
        } else {
            $path = APPLICATION_PATH.$url;
            $url = SITE_URL.$url;
        }
        if(is_dir($path)) {
            $dir = new \DirectoryIterator($path);
            $toAdd = array("dirsBefore" => array(), "files" => array(), "dirsAfter" => array());
            foreach($dir as $file) {
                $filename = $file->getFilename();
                if(!$file->isDot()) {
                    if(!$file->isDir()) {
                        if($filename{0} != "." && substr($filename, -strlen($type)-1) == ".".$type) {
                            $toAdd["files"][] = $filename;
                        }
                    } elseif($filename{0} == "_") {
                        $toAdd["dirsBefore"][] = $filename;
                    } elseif(substr($filename, -1) == "_") {
                        $toAdd["dirsAfter"][] = $filename;
                    }
                }
            }
            foreach($toAdd as $fileType => $filesArray) {
                foreach($filesArray as $filename) {
                    if($inputurl != "") {
                        $filename = $inputurl."/".$filename;
                    }
                    $this->_addFile($filename, $inputContext, $type);
                }
            }
            // fluent interface
            return $this;
        } elseif(!is_file($path)) { // only add existing files
            return $this;
        } 
        
        if($context[0] == "backend") {
            $this->_backendFiles[$type][$url] = $path;
        } else {
            $this->_files[$type][$url] = $path;
        }
        
        // if there is a directory called as the file (e.g. style.css and style)
        // this function is called recursively with this directory
        // and because this function self checks if the given file/directory exists
        // it can be done without a check here (we give it a try)
        $recursiveDirectoryUrl = substr($inputurl, 0, -strlen($type)-1);
        // recursive call
        $this->_addFile($recursiveDirectoryUrl, $inputContext, $type);
        
        
        // fluent interface
        return $this;
    }

    /**
     * Generate the head of the html output
     * 
     * @return string The head of the html
     */
    private function _generateHead() {
        $head = "<head>\n";
        $head .= "<meta charset='utf-8' />";
        foreach($this->_meta as $meta) {
            $head .= "<meta $meta />\n";
        }
        $head .= "<title>".(empty($this->_title) ? Registry::getSiteName() : $this->_title." :: ".Registry::getSiteName())."</title>";
        $head .= "<link rel='icon' type='image/x-icon' href='favicon.ico'></link>";
        foreach($this->_files["css"] as $url => $path) {
            $head .= "<link rel='stylesheet' type='text/css' href='$url'></link>\n";
        }
        foreach($this->_backendFiles["css"] as $url => $path) {
            $head .= "<link rel='stylesheet' type='text/css' href='$url'></link>\n";
        }
        $head .= $this->_generateScriptTags()."</head>";

        return $head;
    }
    
    /**
     * Generate all javascript tags
     * 
     * @return string The javascript tags
     */
    private function _generateScriptTags() {
        $scripts = "";
        
        if($this->getJsVarsString() != "") {
            $scripts .= "<script type='text/javascript'>\n".$this->getJsVarsString()."\n</script>\n";
        }
        
        foreach($this->_files["js"] as $url => $path) {
            $scripts .= "<script type='text/javascript' src='$url'></script>\n";
        }
        foreach($this->_backendFiles["js"] as $url => $path) {
            $scripts .= "<script type='text/javascript' src='$url'></script>\n";
        }
        if($this->getJsCodeString() != "") {
            $scripts .= "<script type='text/javascript'>\n".$this->getJsCodeString()."\n</script>\n";
        }

        return $scripts;
    }

    /**
     * Set the doctype
     * 
     * @param string $doctype The current doctype
     * 
     * @return \pinion\general\Response The response object
     */
    public function setDoctype($doctype) {
        switch ($doctype) {
            case("html4strict"):
                
                break;
            case("html5"):
            default:
                $this->_structure = "<!doctype html>\n<html>\n%s\n</html>";
        }

        // fluent interface
        return $this;
    }
    
    /**
     * Magic Method: Create a tag with an function
     * 
     * @param type $name      The tag name
     * @param type $arguments The attributes and the content of the tag
     * 
     * @example $this->a("pinion", array("href" => "http://www.pinion-cms.org")) 
     * 
     * @return \pinion\general\Response The response object
     */
    public function __call($name, $arguments) {
        $returnString = false;
        if($name{0} == "_") {
            $name = substr($name, 1);
            $returnString = true;
        }
        
        $output = "";
        $attributes = "";
        
        $tag = strtolower($name);
        foreach($arguments as $argument) {
            if(is_string($argument)) {
                $output .= $argument;
            } elseif(is_array($argument)) {
                foreach($argument as $attributename => $attributevalue) {
                    if($attributename == "children") {
                        foreach($attributevalue as $child) {
                            $output .= $child;
                        }
                    } elseif($attributename == "text") {
                        $output .= $attributevalue;
                    } else {
                        $attributes .= " $attributename='$attributevalue'";
                    }
                }
            }
        }
        
        $output = "<$tag$attributes>$output</$tag>";
        
        if($returnString)
            return $output;
        
        $this->write($output);

        // fluent interface
        return $this;
        
    }
}
?>
