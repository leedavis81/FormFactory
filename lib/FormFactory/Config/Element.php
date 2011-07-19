<?php

namespace FormFactory\Config;

/*
 * element configuration class
 */

class Element
{
    // parameters set from modelling
    protected $fieldName;
    protected $type;
    protected $length;
    protected $precision;
    protected $scale;
    protected $nullable;
    protected $unique;
    protected $columnName;
    protected $id;

    // parameters set from custom configurations
    protected $elementType;
    protected $allowedElementTypes = array('button', 'checkbox', 'file', 'hidden', 'password', 'text', 'textarea', 'image', 'multiselect', 'multiCheckbox', 'select', 'radio');
    protected $disabled = false;
    protected $required = false;
    protected $linkData = array();
    protected $value;
    protected $label;

    public function __construct($params = array())
    {
        foreach($params as $key => $value)
        {
            $methodName = 'set' . ucfirst($key);
            if(method_exists($this, $methodName))
            {
                if($methodName == 'setType')
                {
                    // Ensure we load the default element for this type
                    $this->$methodName($value, true);
                } else
                {
                    $this->$methodName($value);
                }

            }
        }
    }

    /**
     *
     * Load in any custom configuration set in the custom config object that effect this element
     * @param unknown_type $formConfigs
     */
    public function loadFormConfigurations($formConfigs = array())
    {
        foreach($formConfigs as $configKey => $configValue)
        {
            switch($configKey)
            {
                case 'disabled':
                    $this->setDisabled();
                    break;
                case 'required':
                    $this->setRequired();
                    break;
                case 'value':
                    $this->setValue($configValue);
                    break;
                case 'label':
                    $this->setLabel($configValue);
                    break;
                case 'linkEntity':
                    $this->setLinkEntity($configValue);
                    break;
                case 'linkData':
                    $this->setLinkManualData($configValue);
                    break;
                case 'elementType':
                    $this->setElementType($configValue);
                    break;
            }
        }
    }

    /**
     * @desc Returns element configuration, writes config to cache for speed boost
     * @var string name
     * @var array entity definition
     * @var array configurations
     * @return Element configuration
     */
    public static function getConfig($entity, $definitions = array(), $configurations = array())
    {
        // are we caching element configs (check the general)
        if (isset(General::getInstance()->getConfig()->ff->cache))
        {
            $cacheConfig = General::getInstance()->getConfig()->ff->cache;

            $cache = \Zend_Cache::factory(
                (!is_null($cacheConfig->frontend->name) ? $cacheConfig->frontend->name : 'Core'),
                (!is_null($cacheConfig->backend->name) ? $cacheConfig->backend->name : 'File'),
                (!is_null($cacheConfig->frontend->options) ? $cacheConfig->frontend->options : array()),
                (!is_null($cacheConfig->backend->options) ? $cacheConfig->backend->options : array())
            );

            // check to see if this element has already been written to cache
            $cacheReference = $entity . '_' . $definitions['columnName'];
            if (!$config = $cache->load($cacheReference))
            {
                //cache miss, start a new instance and write it to the cache
                $config = new self($definitions);
                $config->loadFormConfigurations($configurations);
                $cache->save($config, $cacheReference);
                return $config;
            }
            // return the cached item;
            return $config;
        }
        // No caching has been set, return a new instance
        $config = new self($definitions);
        $config->loadFormConfigurations($configurations);
        return $config;
    }

    /**
     * @return the $fieldName
     */
    public function getFieldName()
    {
        return $this->fieldName;
    }

    /**
     * @return the $type
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return the $length
     */
    public function getLength()
    {
        return $this->length;
    }

    /**
     * @return the $precision
     */
    public function getPrecision()
    {
        return $this->precision;
    }

    /**
     * @return the $scale
     */
    public function getScale()
    {
        return $this->scale;
    }

    /**
     * @return the $nullable
     */
    public function getNullable()
    {
        return $this->nullable;
    }

    /**
     * @return the $unique
     */
    public function getUnique()
    {
        return $this->unique;
    }

    /**
     * @return the $columnName
     */
    public function getColumnName()
    {
        return $this->columnName;
    }

    /**
     * @return the $id
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param field_type $fieldName
     */
    public function setFieldName($fieldName)
    {
        $this->fieldName = $fieldName;
    }

    /**
     *
     * Set the field type, option to set the element type default
     * @param string $type
     * @param boolean $setElementType
     */
    public function setType($type, $setElementType = false)
    {

        // based on the type passed, set the default element associated with it
        switch($type)
        {
            // Doctrine 1.2 specific types
            case 'enum':
                $this->setElementType('select');
                break;
            case 'gzip':
            case 'timestamp':
                $this->setElementType('text');
                break;
            case 'blob':
            case 'clob':
                $this->setElementType('textarea');
                break;
            // Types shared between Doctrine 1.2 & 2
            case 'boolean':
            case 'integer':
            case 'float':
            case 'decimal':
            case 'string':
            case 'text':
            case 'array':
            case 'object':
            case 'time':
            case 'date':
                $this->setElementType('text');
                break;

            // Doctrine 2 specific types
            case 'smallint':
            case 'bigint':
            case 'datetime':
                $this->setElementType('text');
                break;
            default:
                throw new Exception('Unknown column type ' . $type);
                break;
        }

        $this->type = $type;
    }

    /**
     * @param field_type $length
     */
    public function setLength($length)
    {
        $this->length = $length;
    }

    /**
     * @param field_type $precision
     */
    public function setPrecision($precision)
    {
        $this->precision = $precision;
    }

    /**
     * @param field_type $scale
     */
    public function setScale($scale)
    {
        $this->scale = $scale;
    }

    /**
     * @param field_type $nullable
     */
    public function setNullable($nullable)
    {
        $this->nullable = $nullable;
    }

    /**
     * @param field_type $unique
     */
    public function setUnique($unique)
    {
        $this->unique = $unique;
    }

    /**
     * @param field_type $columnName
     */
    public function setColumnName($columnName)
    {
        $this->columnName = $columnName;
    }

    /**
     * @param field_type $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return the $elementType
     */
    public function getElementType()
    {
        return $this->elementType;
    }

    /**
     * @param field_type $elementType
     */
    public function setElementType($elementType)
    {
        if(!in_array($elementType, $this->allowedElementTypes))
        {
            throw new Exception('Element type ' . $elementType . ' not allowed, must be one of ' . implode(', ', $this->allowedElementTypes));
        }
        $this->elementType = $elementType;
    }

    public function setDisabled()
    {
        $this->disabled = true;
    }

    public function isDisabled()
    {
        return $this->disabled;
    }

    public function setRequired()
    {
        $this->required = true;
    }

    public function isRequired()
    {
        return $this->required;
    }

    /**
     * Set and process a linking entity
     * @var array configuration parameters entityName | entityField | entityFieldValue
     */
    protected function setLinkEntity($params)
    {
        // Make sure link data hasn't already been set on this element
        if($this->hasLinkData())
        {
            throw new Exception('Element ' . $this->getColumnName() . ' already has link data set');
        }

        // Ensure an entity name has been set
        if(!array_key_exists('entityName', $params))
        {
            throw new Exception('Entity linking for column ' . $this->getColumnName() . ' must have an entityName set');
        }
        // Ensure an entity field has been set
        if(!array_key_exists('entityField', $params))
        {
            throw new Exception('Entity linking for column ' . $this->getColumnName() . ' must have an entityField set');
        }
        // Ensure an entity field value has been set
        if(!array_key_exists('entityFieldValue', $params))
        {
            throw new Exception('Entity linking for column ' . $this->getColumnName() . ' must have an entityFieldValue set');
        }

        $linkData = General::getInstance()->getAdapter()->getDataFromEntity($params['entityName'], array($params['entityField'], $params['entityFieldValue']), (isset($params['filters'])) ? $params['filters'] : null);

        // Process the return data, and set it to this object
        if(!empty($linkData))
        {
            foreach($linkData as $item)
            {
                $this->setLinkData($item[$params['entityField']], $item[$params['entityFieldValue']]);
            }
        }
    }

    /**
     * Add manually set values to the link data
     * @param array $params
     */
    protected function setLinkManualData($params)
    {
        if(!array_key_exists('names', $params))
        {
            throw new Exception('Add manual link data for  ' . $this->getColumnName() . ' requires a names definition');
        }
        if(!array_key_exists('values', $params))
        {
            throw new Exception('Add manual link data for  ' . $this->getColumnName() . ' requires a values definition');
        }

        $names = array_map('trim', explode(',', $params['names']));
        $values = array_map('trim', explode(',', $params['values']));

        // Ensure they're the same size
        if(sizeof($names) != sizeof($values))
        {
            throw new Exception('Inconsistent names / values size on manual link data');
        }

        for($x = 0; $x < sizeof($names); $x++)
        {
            $this->setLinkData($names[$x], $values[$x]);
        }
    }

    /**
     * Set link data for this element
     * @var string link field name
     * @var string link field value
     */
    protected function setLinkData($name, $value)
    {
        switch($this->getElementType())
        {
            case 'multiselect':
            case 'multiCheckbox':
            case 'select':
            case 'radio':
                break;
            default:
                throw new Exception('Cannot add link data to ' . $this->getColumnName() . ' unless the element is one of multiselect|multiCheckbox|select|radio');
                break;
        }
        // Set to the same order required when injecting multiOptions into zend_form_element_multi
        $this->linkData[$name] = $value;
    }

    public function getLinkData()
    {
        return $this->linkData;
    }

    public function hasLinkData()
    {
        if(!empty($this->linkData))
        {
            return true;
        }
        return false;
    }

    /**
     * @return the $value
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @return the $label
     */
    public function getLabel()
    {
        if ($this->label === null)
        {
            // If it hasn't been set, provide a default
            $filter = new \Zend_Filter_Word_UnderscoreToSeparator(' ');
            $this->label = ucwords($filter->filter($this->getColumnName()));
        }

        return $this->label;
    }

    /**
     * @param field_type $value
     */
    public function setValue($value)
    {
        $this->value = $value;
    }

    /**
     * @param field_type $label
     */
    public function setLabel($label)
    {
        $this->label = $label;
    }

}