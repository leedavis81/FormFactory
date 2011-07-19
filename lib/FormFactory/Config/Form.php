<?php

namespace FormFactory\Config;
/*
 * Loads a form configuration
 * Merges default configurations with preset configurations
 */

class Form
{

    protected $configOptions;

    protected $entity;
    protected $path;
    protected $section;
    protected $config;

    /**
     * element configurations to be pulled into an element configuration object
     * @var unknown_type
     */
    protected $elementConfigs = array();

    public function __construct($entity, $configOptions)
    {
        $this->setEntity($entity);
        $this->setConfigs($configOptions);

        // Load the config on construct, to prepare custom element configurations
        try
        {
            $this->loadConfig();
        } catch (Exception $e)
        {
        }

    }

    public function loadConfig()
    {
        $filter = new \Zend_Filter_Word_CamelCaseToUnderscore();
        $fileName = strtolower($filter->filter($this->getEntity()));
        $fullPath = $this->getPath() . DIRECTORY_SEPARATOR . $fileName . '.' . $this->getFileType();
        if (!file_exists($fullPath))
        {
            throw new Exception('Unable to retrieve config file from path ' . $fullPath);
        }

        $this->config = Helper::start($fullPath, $this->getSection())->getObject();

        // Load up configuration options into this object
        $this->loadCustomConfigurations();
    }

    /**
     * Load up custom configurations to this object
     */
    protected function loadCustomConfigurations()
    {
        $config = $this->getConfig()->toArray();
        foreach ($config['config'] as $configKey => $configValue)
        {
            if (is_string($configValue))
            {
                // Load up form level configurations to the object
                switch($configKey)
                {
                    // process form level definitions
                    case 'disabled':
                        $disabledElements = array_map('trim', explode(',', $configValue));
                        foreach ($disabledElements as $disabledElement)
                        {
                            $this->setElementConfig($disabledElement, array('disabled' => true));
                        }
                        break;
                }
            } elseif (is_array($configValue))
            {
                // these are field level configurations and should be populated in the element configuration object
                $this->setElementConfig($configKey, $configValue);
            }
        }
    }

    /**
     * get any element configurations read in from the form config
     * @param string $elementName
     * @return array
     */
    public function getElementConfig($elementName)
    {
        if (isset($this->elementConfigs[$elementName]))
        {
            return $this->elementConfigs[$elementName];
        }
        return array();
    }

    protected function setElementConfig($elementName, $options)
    {
        if (isset($this->elementConfigs[$elementName]))
        {
            $this->elementConfigs[$elementName] = array_merge($this->elementConfigs[$elementName], $options);
        } else
        {
            $this->elementConfigs[$elementName] = $options;
        }
    }

    public function setConfigs(array $configArray)
    {
        foreach ($configArray as $key => $value)
        {
            $this->setConfig($key, $value);
        }
    }

    public function setConfig($key, $value)
    {
        $this->configOptions[$key] = $value;
    }

    public function getConfig()
    {
        if (empty($this->config))
        {
            try
            {
                $this->loadConfig();
            } catch (Exception $e)
            {
            }
        }
        return $this->config;
    }

    public function setEntity($entity)
    {
        $this->entity = $entity;
    }

    public function getEntity()
    {
        return $this->entity;
    }

    public function getPath()
    {
        if (isset($this->configOptions['path']))
        {
            return $this->configOptions['path'];
        }
        throw new Exception('Required path not set to configuration options');
    }

    public function getFileType()
    {
        if (isset($this->configOptions['filetype']))
        {
            return $this->configOptions['filetype'];
        }
        throw new Exception('Required filetype not set to configuration options');
    }

    public function getSection()
    {
        if (isset($this->configOptions['section']))
        {
            return $this->configOptions['section'];
        }
        // No section has been set
        return null;
    }

}