<?php
/**
 * Class PHP_Class_Writer
 * 
 * PHP version 5.3
 * 
 * @category   files
 * @package    files
 * @subpackage classwriter
 * @author     Friedolin Förder <friedolin.foerder@pinion-cms.org>
 * @license    MIT License
 * @link       http://www.pinion-cms.org
 */

namespace pinion\files\classwriter;


class PHP_Class_Writer implements IClassWriter {
    
    /**
     * The content of the class file
     * 
     * @var string $_output
     */
    protected $_output = "";
    
    /**
     * An array with information about the class
     * 
     * @var array $_classInfo
     */
    protected $_classInfo = array();
    
    /**
     * The namespace of the class
     * 
     * @var string $_namespace 
     */
    protected $_namespace = "";
    
    /**
     * The use calls of the class
     * 
     * @var array $_uses 
     */
    protected $_uses = array();
    
    /**
     * The methods of the class
     * 
     * @var array $_methods
     */
    protected $_methods = array();
    
    /**
     * The attributes of the class
     * 
     * @var array $_attributes 
     */
    protected $_attributes = array();
    
    /**
     * The current margin in the file
     * 
     * @var string $_margin 
     */
    protected $_margin = "";
    
    /**
     * One indention unit
     * 
     * @var string $_oneMargin 
     */
    protected $_oneMargin = "    ";
    
    /**
     * The current path to save
     * 
     * @var string $_savePath 
     */
    protected $_savePath;

    /**
     * Set the path to save
     * 
     * @param string $path 
     */
    public function setSavePath($path) {
        $this->_savePath = $path;
    }
    
    /**
     * Set the namespace of the class
     * 
     * @param string $namespace The namespace of the class
     * 
     * @return \pinion\files\classwriter\PHP_Class_Writer 
     */
    public function setNamespace($namespace) {
        $this->_namespace = $namespace;
        
        // fluent interface
        return $this;
    }
    
    /**
     * Set which classes should be used
     * 
     * @param array $uses The classes, which should be used
     * 
     * @return \pinion\files\classwriter\PHP_Class_Writer 
     */
    public function setUses(array $uses) {
        $this->_uses = $uses;
        
        // fluent interface
        return $this;
    }

    /**
     * Set the class
     * 
     * @param string      $classname     The name of the class
     * @param null|string $access        The access type of the class
     * @param null|string $extension     The extended class
     * @param array       $implementions The interface implementations of the class 
     * 
     * @return \pinion\files\classwriter\PHP_Class_Writer 
     */
    public function setClass($classname, $access = null, $extension = null, array $implementions = array()) {
        $this->_classInfo = array(
            "name" => ucfirst($classname),
            "access" => $access,
            "extension" => $extension,
            "implementions" => $implementions
        );

        // fluent interface
        return $this;
    }

    /**
     * Add a method
     * 
     * @param string $name    The name of the method
     * @param string $content The content of the method
     * @param string $access  The access type of the method
     * 
     * @return \pinion\files\classwriter\PHP_Class_Writer 
     */
    public function addMethod($name, $content="", $access="public") {
        $this->_methods[] = array(
            "name" => $name,
            "access" => $access,
            "content" => $content,
        );

        // fluent interface
        return $this;
    }

    /**
     * Add an attribute
     * 
     * @param string $name   The name of the attribute
     * @param string $access The access type of the attribute
     * @param mixed  $value  The value of the attribute
     * 
     * @return \pinion\files\classwriter\PHP_Class_Writer 
     */
    public function addAttribute($name, $access="public", $value=null) {
        $this->_attributes[] = array(
            "name" => $name,
            "access" => $access,
            "value" => $value,
        );

        // fluent interface
        return $this;
    }

    /**
     * Render the header of the file
     * 
     * @return string The header of the file
     */
    protected function renderFileHeader() {
        $output = "<?php"   .$this->newLine()
                            .$this->newLine()
                            .$this->renderClassHeader()
                            .$this->newLine()
                            ."?>";

        return $output;
    }

    /**
     * Render the header of the class
     * 
     * @return string The header of the class
     */
    protected function renderClassHeader() {
        $classHeader = "";
        if(!empty($this->_namespace)) {
            $classHeader .= "namespace ".$this->_namespace.";\n\n";
        }
        if(!empty($this->_uses)) {
            foreach($this->_uses as $use) {
                $classHeader .= "use $use;\n";
            }
            $classHeader .= "\n";
        }
        if(!empty($this->_classInfo['access'])) {
            $classHeader .= $this->_classInfo['access']." ";
        }
        $classHeader .= "class ".$this->_classInfo['name']." ";
        if(!empty($this->_classInfo['extension'])) {
            $classHeader .= "extends ".$this->_classInfo['extension']." ";
        }
        if(!empty($this->_classInfo['implementions'])) {
            $classHeader .= "implements ".join(", ", $this->_classInfo['implementions'])." ";
        }
        $classHeader .= $this->openBracket()
                        .$this->renderAttributes()
                        .$this->closeBracket();

        return $classHeader;
    }

    /**
     * Render the attributes of the class
     * 
     * @return string The attributes of the class
     */
    protected function renderAttributes() {
        $attributes = "";

        foreach($this->_attributes as $attribute) {
            $attributes .= $attribute['access']." $".$attribute['name'];
            if($attribute['value']) {
                if(is_array($attribute['value'])) {
                    $attribute['value'] = $this->arrayToString($attribute['value']);
                } elseif(is_bool($attribute['value'])) {
                    $attribute['value'] = $attribute['value'] ? "true" : "false";
                } elseif(is_string($attribute['value'])) {
                    $attribute['value'] = '"'.$attribute['value'].'"';
                }
                $attributes .= " = ".$attribute['value'];
            }
            $attributes .= ";".$this->newLine();
        }
        $attributes .= $this->newLine();

        return $attributes;
    }

    /**
     * Save the class
     * 
     * @param string      $directory The directory, in which the class should be created
     * @param null|string $filename  If the classname is not the same as the file name, provide the filename here
     * 
     * @return \pinion\files\classwriter\PHP_Class_Writer
     */
    public function save($directory = null, $filename = null) {
        if($directory == null) {
            if($this->_savePath == null) {
                $directory = "";
            } else {
                $directory = $this->_savePath;
            }
        }
        $this->_output = $this->renderFileHeader();

        if(empty($filename)) {
            $filename = $this->_classInfo['name'].".php";
        }
        file_put_contents($directory.DIRECTORY_SEPARATOR.$filename, $this->_output);

        $this->reset();

        // fluent interface
        return $this;
    }

    /**
     * Reset the ClassWriter 
     * 
     * @return \pinion\files\classwriter\PHP_Class_Writer
     */
    private function reset() {
        $this->_classInfo = array();
        $this->_attributes = array();
        $this->_methods = array();
        $this->_margin = "";
        $this->_output = "";
        
        // fluent interface
        return $this;
    }

    /**
     * Convert an array to a string
     * 
     * @param array $array The array, which should be converted
     * 
     * @return string A string of the converted array 
     */
    public function arrayToString(array $array) {
        $output = "array".$this->openBracket("(");
        $count = 0;
        foreach($array as $key => $value) {
            $count++;
            if(is_array($value)) {
                $value = $this->arrayToString($value);
            } elseif(is_int($value) || is_string($value)) {
                $value = "'$value'";
            }
            $newLine = $count != count($array) ? $this->newLine() : "";
            $key = is_numeric($key) ? "" : "'$key' => ";
            $output .= "$key$value,$newLine";
        }
        return $output.$this->closeBracket(")", "newLine", null);
    }

    /**
     * Start with a bracket
     * 
     * @param string $sign The bracket sign
     * 
     * @return string A bracket plus new line and indention 
     */
    protected function openBracket($sign="{") {
        $this->_margin .= $this->_oneMargin;
        return $sign.$this->newLine();
    }

    /**
     * End with a bracket
     * 
     * @param string $sign       The bracket sign
     * @param string $beforeSign Determine what should be done before the bracket sign
     * @param string $afterSign  Determine what should be done after the bracket sign
     * 
     * @return string The complete bracket ending 
     */
    protected function closeBracket($sign="}", $beforeSign="newLine", $afterSign="newLine") {
        $this->_margin = substr($this->_margin, 0, strlen($this->_margin)-strlen($this->_oneMargin));
        $beforeSign = $beforeSign == "newLine" ? $this->newLine() : "";
        $afterSign = $afterSign == "newLine" ? $this->newLine() : "";
        return $beforeSign.$sign.$afterSign;
    }

    /**
     * Create a new line
     * 
     * @return string A new line with indention 
     */
    protected function newLine() {
        return "\n$this->_margin";
    }
}
?>
