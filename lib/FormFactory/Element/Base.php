<?php

class Base
{

 /*
  * Zend form element
  */
  protected $element;
 /*
  * element name
  */
  protected $name;
  /*
   * element parameters passed for the doctrine record model
   */
  protected $params;
  /*
   * Element type name
   */
  protected $type;

  public function __construct($name, $params = array())
  {
    $this->name = $name;
    $this->params = $params;
  }

  public function setName($name)
  {
    $this->name = $name;
  }


  public function createElement($type)
  {
    $this->type = $type;

    if (empty($this->name))
    {
      throw new Exception('Element name must be set before zend form element can be created');
    }
    $elementClass = 'Zend_Form_Element_' . $this->type;
    if (!class_exists($elementClass))
    {
      throw new Exception('Unable to instantiate zend form element class ' . $elementClass);
    } else
    {
      $this->element = new $elementClass($this->name);
    }

    // set default attributes, can be overwritten in extended class
    $this->setLabel();
    $this->setClass();
    $this->setValue();
    $this->setAttributes();
    $this->setOptions();

    $this->addDecorators();
    $this->addValidators();
  }

  /*
   * Set the element ID, zend form automatically assigns the ID from the element name
   * Only need to use this for override
   */
  public function setId($id = null)
  {
    if (!is_null($id))
    {
      $this->element->setAttrib('id', $id);
    }
  }

  public function setClass($class = null)
  {
    if (!is_null($class))
    {
      $this->element->setAttrib('class', $class);
    } else
    {
      $this->element->setAttrib('class', strtolower($this->type));
    }
  }


  public function setAttributes()
  {
    if (!empty($this->params['attributes']))
    {
      $this->element->setAttribs($this->params['attributes']);
    }
  }


  public function setOptions()
  {
    if (!empty($this->params['options']))
    {
      $this->element->setOptions($this->params['options']);
    }
  }


  /*
   * Set the element label, can be defined via config, otherwise element name is used
   */
  public function setLabel($label = null)
  {
    if (!is_null($label))
    {
      $this->element->setLabel($label);
    } elseif (!empty($this->params['label']))
    {
       $this->element->setLabel($this->params['label']);
    } else
    {
      $filter = new Zend_Filter_Word_UnderscoreToSeparator();
      $label = $filter->filter($this->name);
      $this->element->setLabel(ucwords($label));
    }
  }

  public function setValue($value = null)
  {
    if (!is_null($value))
    {
      $this->element->setValue($value);
    } elseif (!empty($this->params['value']))
    {
       $this->element->setValue($this->params['value']);
    }
  }

  /*
   * Pull in any defined decorator classes
   */
  public function addDecorators()
  {
    if (!empty($this->params['decorators']) && is_array($this->params['decorators']))
    {
      foreach ($this->params['decorators'] as $decorator)
      {
        if (@class_exists($validator) || @class_exists('Zend_Form_Decorator_' . $validator))
        {
          $this->element->addValidator($validator);
        }
      }
    }

  }
  /*
   * Pull in any defined validators
   */
  public function addValidators()
  {
    if (isset($this->params['required']) && $this->params['required'] == true)
    {
      $this->element->setRequired(true);
    }
    if (!empty($this->params['validators']) && is_array($this->params['validators']))
    {
      foreach ($this->params['validators'] as $validator)
      {
        if (@class_exists('Zend_Validate_' . $validator))
        {
          $this->element->addValidator($validator);
        } elseif(@class_exists($validator))
        {
           $this->element->addValidator(new $validator);
        }

      }
    }
  }
  /*
   * Used for adding options to multi elements
   */
  public function addOptions()
  {

    // Add any hardcoded values
    if (isset($this->params['fieldValues'])
          && isset($this->params['fieldNames'])
          && (array) $this->params['fieldValues']
          && (array) $this->params['fieldNames'])
    {
      $arraySize = sizeof($this->params['fieldValues']);
      for ($x = 0; $x < $arraySize; $x++)
      {
        $label = trim($this->params['fieldNames'][$x]);
        $value = trim($this->params['fieldValues'][$x]);
        $this->element->addMultiOption($value, $label);
      }
    }

    // add the options defined in a relating table, if it exists
    if (!empty($this->params['linkTable']))
    {
      $this->addLinkTableOption();
      return true;
    } elseif (isset($this->params['values']) && is_array($this->params['values']))
    {
      // If no options defined, add the options defined in the doctrine model
      foreach($this->params['values'] as $option)
      {
        $label = trim(ucfirst($option));
        $value = trim(strtolower($option));
        $this->element->addMultiOption($value, $label);
      }
      return true;
    } else
    {
      // if nothing is added return false, so the parent class can throw an exception
      // This is a multi form element so it MUST have options
      $options = $this->element->getMultiOptions();
      if (!empty($options))
      {
        return true;
      }
      return false;
    }
  }


  /*
   * add options to an element from a linking table
   */
  public function addLinkTableOption()
  {
    if (empty($this->params['linkTable']['tableName'])) throw new Exception('Missing parameter tableName on linkTable for element ' . $name);
    if (empty($this->params['linkTable']['nameField'])) throw new Exception('Missing parameter nameField on linkTable for element ' . $name);
    if (empty($this->params['linkTable']['valueField'])) throw new Exception('Missing parameter valueField on linkTable for element ' . $name);

    $modelName = Formfactory_Core_Config::getModelsPrefix();
    $filter  = new Zend_Filter_Word_UnderscoreToCamelCase();
    $modelName .= $filter->filter($this->params['linkTable']['tableName']);
    if (!class_exists($modelName))
    {
      throw new Exception('Link model ' . $modelName . ' not found');
    }
    $model = new $modelName();
    $columns = $model->getTable()->getColumns();
    if (!array_key_exists($this->params['linkTable']['nameField'], $columns))
    {
      throw new Exception('Unable to find link table nameField \'' . $this->params['linkTable']['nameField'] . '\' in model ' . $modelName);
    }
    if (!array_key_exists($this->params['linkTable']['valueField'], $columns))
    {
      throw new Exception('Unable to find link table valueField \'' . $this->params['linkTable']['valueField'] . '\' in model ' . $modelName);
    }
    // lookup the values in the linking table
    $q = Doctrine_Query::create()
      ->select($this->params['linkTable']['nameField'] . ', ' . $this->params['linkTable']['valueField'])
      ->from($modelName);
    $results = $q->fetchArray();
    $q->free();
    unset($q);
    foreach ($results as $result)
    {
      $label = trim($result[$this->params['linkTable']['nameField']]);
      $value = trim($result[$this->params['linkTable']['valueField']]);
      $this->element->addMultiOption($value, $label);
    }
  }

  /*
   * Used for image type elements to set the src
   */
  public function setImageSrc($src)
  {
    if (!empty($src))
    {
      $this->element->setImage($src);
      return true;
    } else
    {
      return false;
    }
  }

  /*
   * Return the zend element object
   */
  public function getZendElement()
  {
    return $this->element;
  }

}