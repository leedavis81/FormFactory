<?php
namespace FormFactory\Plugin;

/**
 * Proxy object for linkdata plugin
 * @author lee.davis
 *
 */
abstract class LinkData
{
    /**
     * Overloaded object data
     * @var array $data
     */
    private $data = array();

    
    /**
     * Get element options to be populated as link data. Format array($value => $name)
     * @return array $options
     */
    abstract public function getOptions();

    
    /**
     * Initializer method that can be optionally overridden
     */
    public function init()
    {
    }
    
    
    public function __get($name)
    {
        if (array_key_exists($name, $this->data))
        {
            return $this->data[$name];
        }
    }
    
    public function __set($name ,$value)
    {
        $this->data[$name] = $value;
    }
    
    /**
     * Get all values set to this object
     * return array $data
     */
    public function getData()
    {
        return $this->data;
    }
}