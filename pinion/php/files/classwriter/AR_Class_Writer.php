<?php
/**
 * Class AR_Class_Writer
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


class AR_Class_Writer extends PHP_Class_Writer {

    /**
     * Remove an attribute from a ActiveRecord class
     * 
     * @param string $class          The class from which the attribute should be removed
     * @param string $attribute      The attribute name
     * @param string $attributeClass The class, which relates to the attribute
     * 
     * @throws \Exception Throws an exception if the class or the attribute do not exists
     */
    public function removeAttributeFromClass($class, $attribute, $attributeClass) {
        
        $className = explode("\\", $class);
        $className = end($className);
        
        if(!\is_string($className))
            throw new \Exception("className is not a string!");

        if(! \class_exists($class))
            throw new \Exception("class '$className' does not exist!");

        if(!\property_exists($class, $attribute))
            throw new \Exception("attribute '$attribute' does not exist in class '$className'!");

        
        $current_var = $class::${$attribute};
        foreach($current_var as $key => $value) {
            if($value["class_name"] == $attributeClass) {
                unset($current_var[$key]);
            }
        }

        $attributes = array(
            "table_name" => $class::$table_name,
            "belongs_to" => $class::$belongs_to,
            "has_many" => $class::$has_many,
            "hasPosition" => $class::$hasPosition
        );

        $attributes[$attribute] = $current_var;


        $this->setClass($className, null, "ActiveRecord");
        foreach($attributes as $name => $attr) {
            $this->addAttribute($name, "static", $attr);
        }
        $this->save();

    }

    /**
     * Set a value of an attribute from a ActiveRecord class
     * 
     * @param string $className      The class to which the attribute should be added
     * @param string $attribute      The attribute name
     * @param mixed  $attributeValue The attribute value
     * 
     * @throws \Exception Throws an exception if the class or the attribute do not exists
     */
    public function addValueToAttribute($className, $attribute, $attributeValue) {

        if(!\is_string($className)) {
            throw new \Exception("className is not a string!");
        }
            
        if(!\class_exists($className)) {
            throw new \Exception("class '$className' does not exist!");
        }
            
        if(!\property_exists($className, $attribute)) {
            throw new \Exception("attribute '$attribute' does not exist in class '$className'!");
        }
            

        $attributes = array(
            "table_name" => $class::$table_name,
            "belongs_to" => $className::$belongs_to,
            "has_many" => $className::$has_many,
            "hasPositions" => $className::$hasPositions
        );

        if(isset($attributes[$attribute])) {
            $attributes[$attribute] = array();
        }

        $attributes[$attribute][] = $attributeValue;

        $this->setClass($className, null, "ActiveRecord");
        foreach($attributes as $name => $attr) {
            $this->addAttribute($name, "static", $attr);
        }
        $this->save();
    }

}
?>