<?php
/**
 * The TemplateBuilder search for the right templates of the modules.
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

use \pinion\modules\FrontendModule;
use \pinion\modules\Renderer;
use \pinion\general\Response;
use \pinion\modules\Module;
use \pinion\modules\ModuleManager;

class TemplateBuilder {
    
    /**
     * Singleton
     * 
     * @var TemplateBuilder $_instance
     */
    protected static $_instance;
    
    /**
     * A template count to identify a specific template.
     * Use $uid() (stands for unique id) in your template to get the count
     * 
     * @var int $count 
     */
    private $count = 0;
    
    /**
     * The response object
     * 
     * @var Response $response 
     */
    private $response;
    
    /**
     * The session object
     * 
     * @var Session $session 
     */
    private $session;
    
    /**
     * The current module of the template
     * 
     * @var Module $renderModule 
     */
    private $renderModule;
    
    /**
     * The current id of the renderer
     * 
     * @var int $renderId 
     */
    private $renderId;
    
    /**
     * The path in which the template is located
     * 
     * @var string $templateDirectory
     */
    private $templateDirectory;
    
    /**
     * An array with all render variables
     * 
     * @var array $renderVariables
     */
    private $renderVariables = array();
    
    /**
     * An array with all temporary render variables
     * 
     * @var array $tempVariables
     */
    private $tempVariables = array();
    
    /**
     * A flag which determine if the current renderer is a module
     * 
     * @var boolean $isModule
     */
    private $isModule = true;
    
    /**
     * An array with all recursive steps through the nested templates
     * 
     * @var array $recursivePath
     */
    private $recursivePath = array();
    
    /**
     * An stack of templates
     * 
     * @var array $templateStack
     */
    private $templateStack = array();
    
    /**
     * The id of the current content
     * 
     * @var int $contentId 
     */
    private $contentId = null;
    
    /**
     * An stack with arrays, which hold the current variables of the templates
     * 
     * @var array $renderStack 
     */
    private $renderStack = array();
    
    /**
     * Singleton function
     * 
     * @return TemplateBuilder Returns this instance 
     */
    public static function getInstance() {
        return self::$_instance;
    }
    
    /**
     * The constructor of TemplateBuilder
     * 
     * @param Response $response The response object
     * @param Session  $session  The session object
     */
    public function __construct(Response $response, Session $session) {
        $this->response = $response;
        $this->session = $session;
        
        $this->renderStack[] = array(
            "renderModule"      => $this->renderModule,
            "renderId"          => $this->renderId,
            "templateDirectory" => $this->templateDirectory,
            "renderVariables"   => $this->renderVariables,
            "tempVariables"     => $this->tempVariables,
            "isModule"          => $this->isModule,
            "recursivePath"     => $this->recursivePath,
            "templateStack"     => $this->templateStack,
            "contentId"         => $this->contentId
        );
        
        self::$_instance = $this;
    }
    
    /**
     * Render one template of a renderer
     * 
     * @param string         $templatename The name of the template
     * @param Renderer       $module       An renderer object
     * @param null|\stdClass $content      An object with content attributes
     * 
     * @return string The html output of the renderer 
     */
    public function renderTemplateOfRenderer($templatename, Renderer $module, $content = null) {
        $ok = $this->_setRenderVariables($module, $content);
        if($ok) {
            \ob_start();
            $this->renderTemplate($templatename);
            $this->_reset();
            return \ob_get_clean();
        }
    }
    
    /**
     * Set the variables of the renderer
     * 
     * @param Renderer       $module  The module
     * @param null|\stdClass $content An object with content attributes
     * 
     * @return boolean True if the variables where set, false otherwise
     */
    private function _setRenderVariables(Renderer $module, $content = null) {
        $this->renderModule = $module;
        $this->templateDirectory = "standard";
        if(isset($content)) {
            $this->renderId = (int)$content->moduleid;
            $this->contentId = (int)$content->id;
            $this->templateDirectory = $content->templatepath != null ? $content->templatepath : "standard";
        }
        
        $data = null;
        if($module instanceof FrontendModule) {
            $data = $module->data->find_by_id($module->name, $this->renderId);
            if(is_null($data)) {
                if(isset($content)) {
                    // cleanup
                    $content->delete();
                }
                return false;
            }
        } 
        $this->renderVariables = $module->setFrontendVars($data);
        
        $this->renderStack[] = array(
            "renderModule"      => $this->renderModule,
            "renderId"          => $this->renderId,
            "templateDirectory" => $this->templateDirectory,
            "renderVariables"   => $this->renderVariables,
            "tempVariables"     => $this->tempVariables,
            "isModule"          => $this->isModule,
            "recursivePath"     => $this->recursivePath,
            "templateStack"     => $this->templateStack,
            "contentId"         => $this->contentId
        );
        
        if(isset($content) && $this->contentId >= 0) {
            $this->response
                ->addJsVariable("content.".$this->contentId.".data", $data->attributes(), true)
                ->addJsVariable("content.".$this->contentId.".content", $content->attributes(), true)
                ->addJsVariable("content.".$this->contentId.".vars", $this->renderVariables, true);
            
            if($module instanceof Module) {
                $module->dispatchEvent("renderTemplate", array("id" => $content->id, "vars" => $this->renderVariables));
            }
        }
        return true;
    }

    /**
     * Render a template of the renderer
     * 
     * @param string $templatename The name of the template
     */
    public function renderTemplate($templatename) {
        
        $firstTimeRendering = false;
        if($this->isModule && !isset($this->templateStack[$this->renderModule->name])) {
            $this->templateStack[$this->renderModule->name] = "{$this->renderModule->name}:$templatename";
            $firstTimeRendering = true;
        }
        $this->isModule = true;
        
        $this->_renderTemplateWithPaths($this->getPaths($templatename), ($templatename == "_main" || isset($this->renderModule->template)));
    }
    
    /**
     * Get all possible template paths of the renderer
     * 
     * @param string $templatename The name of the template
     * 
     * @return array An array with all possible template paths of the renderer 
     */
    protected function getPaths($templatename) {
        $paths = array();
        
        $renderModules = ($this->renderModule instanceof FrontendModule) ? $this->renderModule->getReplacements() : array();
        $renderModules[] = $this->renderModule->name;
        $renderModules = array_reverse($renderModules);
        
        // if the templatename is main and if there is
        // a template set in code, try to find a template with this name
        if($templatename == "_main" && isset($this->renderModule->template)) {
            foreach($renderModules as $renderModule) {
                // first search in the template directory
                $paths[] = array(
                    "path" => TEMPLATES_PATH.Registry::getTemplate()."/{$renderModule}/{$this->templateDirectory}",
                    "templatename" => $this->renderModule->template,
                    "context" => "template:{$renderModule}/{$this->templateDirectory}"
                );
                if($this->templateDirectory != "standard") {
                    $paths[] = array(
                        "path" => TEMPLATES_PATH.Registry::getTemplate()."/{$renderModule}/standard",
                        "templatename" => $this->renderModule->template,
                        "context" => "template:{$renderModule}/{$this->templateDirectory}"
                    );
                }
                // now search in the module
                $paths[] = array(
                    "path" => MODULES_PATH."{$renderModule}/templates",
                    "templatename" => $this->renderModule->template,
                    "context" => "module:{$renderModule}"
                );
            }
        }
        
        foreach($renderModules as $renderModule) {
            // first search in the template directory
            $paths[] = array(
                "path" => TEMPLATES_PATH.Registry::getTemplate()."/{$renderModule}/{$this->templateDirectory}",
                "templatename" => $templatename,
                "context" => "template:{$renderModule}/{$this->templateDirectory}"
            );
            if($this->templateDirectory != "standard") {
                $paths[] = array(
                    "path" => TEMPLATES_PATH.Registry::getTemplate()."/{$renderModule}/standard",
                    "templatename" => $templatename,
                    "context" => "template:{$renderModule}/{$this->templateDirectory}"
                );
            }
            // now search in the module
            $paths[] = array(
                "path" => MODULES_PATH."{$renderModule}/templates",
                "templatename" => $templatename,
                "context" => "module:{$renderModule}"
            );
        }
        
        return $paths;
    }
    
    /**
     * Render a template by indicating all possible paths
     * 
     * @param array   $_paths     All possible template paths
     * @param boolean $_withFiles Set to true if the javascript and stylesheet files should also be loaded
     * 
     * @return boolean True if the template was rendered, false otherwise
     */
    protected function _renderTemplateWithPaths($_paths, $_withFiles) {
        if(\file_exists("{$_paths[0]['path']}/{$_paths[0]['templatename']}.php")) {
            
            // set shortcuts
            $_this = $this;
            $_count = $this->count++;
            $uid = function($_print = true) use($_count) {
                if($_print === false) {
                    return $_count;
                } else {
                    print $_count;
                }
            };
            $get = function($var) use($_this) {
                return $_this->get($var);
            };
            $module = function($_module) use($_this) {
                return $_this->module($_module);
            };
            $template = function($_template) use($_this) {
                $_this->renderTemplate($_template);
            };
            $translate = function() {
                $args = func_get_args();
                $last = end($args);
                if(is_bool($last)) {
                    array_pop($args);
                }
                
                if(count($args) > 1 && is_array($args[1])) {
                    if(isset($args[1][$_SESSION["lang"]])) {
                        // get the translations from the second argument
                        $result = $args[1][$_SESSION["lang"]];
                    } else {
                        // the translation is the current string
                        $result = $args[0];
                    }
                } else {
                    $result = call_user_func_array(array(ModuleManager::getInstance(), "translate"), $args);
                }
                
                if($last === false) {
                    return $result;
                } else {
                    print $result;
                }
            };
            $_session = $this->session;
            $date = function($timestamp = null, $format = "default") use($_this, $_session) {
                $args = func_get_args();
                
                $last = null;
                if(!empty($args)) {
                    $last = end($args);
                    if(is_bool($last)) {
                        array_pop($args);
                        if(count($args) < 2) {
                            $format = "default";
                        }
                        if(count($args) < 1) {
                            $timestamp = null;
                        }
                    }
                }
                
                
                if(is_null($timestamp)) {
                    $timestamp = time();
                }
                
                
                $dateformats = Registry::getDateFormats();
                $lang = $_session->getParameter("lang") ?: Registry::getLanguage();
                if(isset($dateformats[$lang][$format])) {
                    $format = $dateformats[$lang][$format];
                } elseif(isset($dateformats[$lang]["default"])) {
                    $format = $dateformats[$lang]["default"];
                } else {
                    $format = "m/d/Y H:i";
                }
                
                $result = date($format, $timestamp);
                
                if($last === false) {
                    return $result;
                } else {
                    print $result;
                }
            };
            foreach($this->tempVariables as $_name => $_tempVar) {
                ${$_name} = $this->_getVariableClosure($_tempVar);
            }
            foreach($this->renderVariables as $_name => $_tempVar) {
                ${$_name} = $this->_getVariableClosure($_tempVar);
            }
            
            $_file = "{$_paths[0]['path']}/{$_paths[0]['templatename']}.php";
            include $_file;
            
            // include all js and css files
            if($_withFiles) {
                // add all css from css-directory
                $this->response->addCss("", $_paths[0]["context"]);

                // add all css from css/$templatename-directory
                $this->response->addCss($_paths[0]['templatename'], $_paths[0]["context"]);

                // add all js from js-directory
                $this->response->addJs("", $_paths[0]["context"]);

                // add all css from js/$templatename-directory
                $this->response->addJs($_paths[0]['templatename'], $_paths[0]["context"]);
            }
            
            return true;
        }
        array_shift($_paths);
        if(count($_paths) == 0) {
            print "<div class='pinion-error'>No template found</div>";
            return false;
        }
        $this->_renderTemplateWithPaths($_paths, $_withFiles);
    }
    
    /**
     * Get a closure for a variable
     * 
     * @param mixed $closureOrVariable A closure or any variable
     * 
     * @return Closure The created closure 
     */
    private function _getVariableClosure($closureOrVariable) {
        $self = $this;
        if($closureOrVariable instanceof \Closure) {
            $closure = function() use($self, $closureOrVariable) {
                $args = func_get_args();
                array_unshift($args, $self);
                return call_user_func_array($closureOrVariable, $args);
            };
        } else {
            $closure = function($print = true) use($self, $closureOrVariable) {
                if($print) {
                    print $closureOrVariable;
                } else {
                    return $closureOrVariable;
                }
            };
        }
        return $closure;
    }
    
    /**
     * Render a template with a given attribute
     * 
     * @param string      $templatename       The name of the template
     * @param string      $attributename      The name of the attribute
     * @param null|string $recursiveAttribute If there is a recursive attribute: The name of this recursive attribute
     * @param array       $options            Options for the rendering, which can be reached in the template via $options()
     */
    public function renderTemplateWithAttribute($templatename, $attributename, $recursiveAttribute = null, array $options = array()) {
        
        $currentArray = $this->renderVariables[$attributename];
        foreach($this->recursivePath as $step) {
            $currentArray = $currentArray[$step][$recursiveAttribute];
        }
        
        $childCount = 0;
        foreach($currentArray as $id => $child) {
            $childCount++;
            
            array_push($this->recursivePath, $id);
            
            // clear temporary variables
            $this->tempVariables = array();
            
            // add temporary variables START
            if(!isset($child[$recursiveAttribute]) || empty($child[$recursiveAttribute])) {
                $this->tempVariables["hasChildren"] = false;
            } else {
                $this->tempVariables["hasChildren"] = true;
            }
            foreach($child as $key => $value) {
                $this->tempVariables[$key] = $value;
            }
            
            $positionClasses = array();
            
            if($childCount == 1)
                $positionClasses[] = "first";
            if($childCount == count($currentArray))
                $positionClasses[] = "last";
            if(empty($positionClasses))
                $positionClasses[] = "middle";
            
            
            $this->tempVariables["position"] = join(" ", $positionClasses);
            $this->tempVariables["zebra"] = ($childCount%2 == 0) ? "even" : "odd"; 
            $this->tempVariables["level"] = count($this->recursivePath) - 1;
            
            $this->tempVariables["options"] = $options;
            // add temporary variables END
            
            
            $max = isset($options["max"]) ? $options["max"] : 10000;
            
            $contents = "";
            if($this->tempVariables["level"] > $max) {
                $contents = ob_get_contents();
            }
            
            // RENDER TEMPLATE
            $this->renderTemplate($templatename);
            
            // clear temporary variables
            $this->tempVariables = array();
            
            if(count($this->recursivePath) > $max+1) {
                ob_clean();
                print $contents;
            }
            
            
            array_pop($this->recursivePath);
        }
    }
    
    /**
     * Render a attribute
     * 
     * @param Renderer $renderer The renderer for the template
     */
    private function _renderAttribute(Renderer $renderer) {
        // TODO test this
        $currentRenderVars = $this->renderVariables;
        $currentRenderModule = $this->renderModule;
        $this->_setRenderVariables($renderer, null);
        $this->isModule = false;
        $this->renderTemplate("_main");
        $this->renderVariables = $currentRenderVars;
        $this->renderModule = $currentRenderModule;
    }
    
    /**
     * Print a variable
     * 
     * @param mixed $variable Any variable of the renderer
     */
    private function _printVariable($variable) {
        if($variable instanceof Renderer) {
            $this->_renderAttribute($variable);    
        } else{
            print $variable;    
        }
    }
    
    /**
     * Return a variable
     * 
     * @param mixed $var       The name of a variable to return
     * @param type  $firstCall Set to false if it's not the first call
     * 
     * @return boolean True if the variable can be rendered, false otherwise
     */
    private function _returnVariable(&$var, $firstCall = true) {
        
        if($var instanceof Renderer) {
            $var = $this->renderTemplateOfRenderer("_main", $var);
            return true;
        }
        if(array_key_exists($var, $this->renderVariables)) {
            $var = $this->renderVariables[$var];
            return true;
        }
        elseif(array_key_exists($var, $this->tempVariables)) {
            $var = $this->tempVariables[$var];
            return true; 
        }
        
        // if no variable was found, lets have a try with lowercase version of $variable
        if($firstCall) {
            $var = strtolower($var);
            return $this->_returnVariable($var, false);
        }
        
        return false;
    }

    /**
     * Render a variable 
     * 
     * @param string $variable A name of a variable to render
     */
    public function render($variable) {
        if($this->_returnVariable($variable)) {
            $this->_printVariable($variable);
        }
    }

    /**
     * Return a variable
     * 
     * @param string $variable A name of a variable to return
     * 
     * @return mixed The value of the variable 
     */
    public function get($variable) {
        if($this->_returnVariable($variable)) {
            return $variable;
        } else {
            // if no variable was found, get it from the module function
            return $this->renderModule->$variable($this);
        }   
    }
    
    /**
     * Return a variable with the magic method
     * 
     * @param string $variable The name of the variable to return
     * 
     * @return mixed The value of the variable 
     */
    public function __get($variable) {
        return $this->get($variable);
    }
    
    /**
     * Call a template function of the current module
     * 
     * @param string $method    The name of the variable
     * @param array  $arguments The arguments for the method to call
     * 
     * @return mixed The result of the called function 
     */
    public function __call($method, $arguments) {
        array_unshift($arguments, $this);
        return call_user_func_array(array($this->renderModule, $method), $arguments);
    }
    
    /**
     * Reset the template variables 
     */
    public function _reset() {
        array_pop($this->renderStack);
        
        $last = end($this->renderStack);
        
        foreach($last as $key => $value) {
            $this->$key = $value;
        }
    }
    
    /**
     * Get the recursive path
     * 
     * @return array The recursive path 
     */
    public function getRecursivePath() {
        return $this->recursivePath;
    }
    
    /**
     * Get a module within the template
     * 
     * @param string $name The name of the module
     * 
     * @return Module A module instance 
     */
    public function module($name) {
        return ModuleManager::getInstance()->module($name);
    }
    
    /**
     * Get the variables of the current renderer
     * 
     * @return array An array with variables of the current renderer 
     */
    public function getVars() {
        return $this->renderVariables;
    }
    
}
?>
