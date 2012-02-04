<?php
namespace FormFactory;

use FormFactory\Config\Exception;

class Form
{
    /**
     * default form name to use for form generation
     * @var string
     */
    protected $formName;

    /**
     * Configuration section to use
     * @var string
     */
    protected $section;

    /**
     * The general, singleton responsible for configurations / adapter
     * @var \FormFactory\Config\General
     */
    protected $general;

    /**
     * Form configuration loaded for entity passed
     * @var unknown_type
     */
    protected $formConfig;

    /**
     * entities / columns to be added to the form
     * @var array
     */
    protected $entities = array();

    /**
     * Zend form object, returned for static calls
     * @var \Zend_Form
     */
    protected $zendForm;

    /**
     * Element configuration generated, these will be added to the form object
     * @var array
     */
    protected $elements;

	/*
    * Can be instantiated, and run as an object
    * @var $entity mixed string or array
    * @configType required to detect how the form should be rendered
    */
    public function __construct($entity = null, $section = null)
    {
        $this->section = $section;
        $this->run($entity);
    }


    protected function run($entity)
    {
        // Set the general
        $this->general = Config\General::getInstance();

        $this->processConfig($entity);
        $this->processColumns();
        $this->createZendForm();
    }

    protected function processConfig($entity)
    {
        if (is_array($entity))
        {
            foreach ($entity as $entityName => $entityValue)
            {
                if (!empty($entityName) && !is_numeric($entityName))
                {
                    $this->processEntity($entityName);
                }

                if (is_array($entityValue))
                {
                    $this->processConfig($entityValue);
                    continue;
                }
                $this->processEntity($entityValue);
            }
        } else if (is_string($entity))
        {
            $this->processEntity($entity);
        } else
        {
            throw new Exception('Unknown entity data type, must be string or array');
        }

    }

    protected function processEntity($entity)
    {
        if ($this->getFormName() === null)
        {
            // If its not been set, set the form name
            $filter = new \Zend_Filter_Word_CamelCaseToUnderscore();
            $this->setFormName(strtolower($filter->filter($entity)));
        }

        // define the form configuration parameters
        $formConfigParams = array(
        	'path' => realpath(str_replace(' ', '', $this->general->getConfigPath())),
            'filetype' => $this->general->getConfigFileType(),
            'section' => $this->section
        );

        // Read in the configuration for the defined model
        $this->formConfig = new Config\Form($entity, $formConfigParams);

        // Pull in the metadata defined for this entity
        $columns = $this->general->getAdapter()->getColumns($this->general->getEntityClassName(($entity)));

        // @todo: Merge any additional columns set via factory config, into model columns


        // If this entity has already been addded, we need to change the name to keep them unique
        $this->entities[] = array(
        	'entityName' => $entity,
            'columns' => $this->makeColumnsUnique($columns, $entity)
        );
    }

    /**
     * Ensure columns added have unique names
     * @param string $columns
     * @param string $entity
     */
    protected function makeColumnsUnique($columns, $entity)
    {
        $result = array_filter($this->entities, $callback = function($item) use ($entity) {
            if ($item['entityName'] == $entity)
            {
                return true;
            }
            return false;
        });

        if (empty($result))
        {
            // We're done, this item is completely unique
            return $columns;
        } else
        {
            $columnCount = sizeof($result);
            foreach ($columns as $columnName => $columnValue)
            {
                // Lets alter the column name
                $columns[$columnName] = $columnValue . '_' . $columnCount;
            }
            return $columns;
        }
    }

    protected function processColumns()
    {
        foreach($this->entities as $entity)
        {
            foreach ($entity['columns'] as $column => $fieldName)
            {
                $definitions = $this->general->getAdapter()->getColumnDefinitions($this->general->getEntityClassName($entity['entityName']), $column);
                // pull in the element configuration
                $elementConfig = Config\Element::getConfig($entity['entityName'], $definitions, $this->formConfig->getElementConfig($column));
                $elementConfig->setColumnName($fieldName);
                // Add the element to this object
                $this->addElement($elementConfig);
            }
        }
    }


    public function createZendForm()
    {
        if (!isset($this->zendForm))
        {
            $this->zendForm = new \Zend_Form();

            // throw in our entity configuration object
            $formConfig = $this->formConfig->getConfig();
            if (isset($formConfig->form))
            {
                $this->zendForm = new \Zend_Form($formConfig->form);
            }

            // set the form name to be the table name, if not already defined in config
            $formName = $this->zendForm->getName();
            if (empty($formName))
            {
                $this->zendForm->setName($this->getFormName());
            }

            $formAction = $this->zendForm->getAction();
            if (empty($formAction))
            {
                // Set the action to the current request route (ie form submits to itself), this can be overidden
                // @todo: remove this, dont want a dependency on Zend_Controller
                $actionPath = \Zend_Controller_Front::getInstance()->getRequest()->getRequestUri();
                if (!empty($actionPath))
                {
                    $this->zendForm->setAction($actionPath);
                }
            }

            // default the form method to post if not set
            if ($this->zendForm->getMethod() === null)
            {
                $this->zendForm->setMethod('post');
            }

            // process the elements we have
            foreach ($this->getElements() as $element)
            {
                //$element = new Config\Element();

                if ($element->isDisabled())
                {
                    // Element is set to disabled, move to the next one
                    continue;
                }

                $elementClass = '\\FormFactory\\Element\\' . ucfirst($element->getElementType());

                if (!class_exists($elementClass, true))
                {
                    // If the class cant be discovered, throw an exception
                    throw new Exception('Unable to find class ' . $elementClass);
                }

                // Check if the element already exists on the form
                /** @var Zend_Form_Element */
                $zendElement = $this->zendForm->getElement($element->getColumnName());
                if ($zendElement === null)
                {
                    $zendElement = new $elementClass($element->getColumnName());
                }

                // No label has been set, provide a default for non hidden elements
                if ($zendElement->getLabel() === null && $element->getElementType() != 'hidden')
                {
                    $zendElement->setLabel($element->getLabel());
                }

                // Set a defined value
                if ($element->getValue() !== null)
                {
                    $zendElement->setValue($element->getValue());
                }

                // Check to see if this has been set to required
                if ($element->isRequired())
                {
                    $zendElement->setRequired(true);
                }

                // See if the element has link data, if so add it
                if ($element->hasLinkData())
                {
                    $zendElement->addMultiOptions($element->getLinkData());
                }

                if (isset($zendElement) && $this->zendForm->getElement($element->getColumnName()) == null)
                {
                    // Add the zend element to the zend form, if its created and doesn't already exist on the form
                    $this->zendForm->addElement($zendElement);
                }

            }
        }
    }

    /**
     * Add elements to the form
     * @param \FormFactory\Config\Element $element
     */
    protected function addElement(\FormFactory\Config\Element $element)
    {
        $this->elements[] = $element;
    }

    /**
     * retrieve all elements that have been set
     * @return array elements
     */
    protected function getElements()
    {
        return $this->elements;
    }

    /**
     * return zend form object
     * @var \Zend_Form
     */
    public function getZendForm()
    {
        return $this->zendForm;
    }

    /**
     * @return the $formName
     */
    protected function getFormName()
    {
        return $this->formName;
    }

	/**
     * @param string $formName
     */
    protected function setFormName($formName)
    {
        $this->formName = $formName;
    }


    /**
     * Quick call utility for a multiple entities
     * @param string $model
     * @param array $params
     */
    public static function build($entity, $section = null)
    {
        $self = new self($entity, $section);
        return $self->getZendForm();
    }


    /**
     * Quick call utility for a single entity
     * @param string $model
     * @param array $params
     */
    public static function __callstatic($entity, $params = null)
    {
        if (isset($params[0]))
        {
            $form = new self($entity, $params[0]);
        } else
        {
            $form = new self($entity);
        }

        return $form->getZendForm();
    }

}