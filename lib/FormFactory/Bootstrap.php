<?php

namespace FormFactory;

use FormFactory\Config\Helper;
use FormFactory\Config\General;

class Bootstrap
{

    public function __construct($config = null)
    {
        if ($config !== null)
        {
            // Pass the config
            $this->setConfig($config);
        }
    }

    /*
     * @var $config either a Zend_Config object or path to a config file
     */
    public function setConfig($config)
    {
        // Generate a Zend_Config object
        $configObject = Helper::start($config)->getObject();

        $general = General::getInstance();
        // Pass the config object to the general
        $general->setConfig($configObject);
    }

    
    public function getConfig()
    {
        return $this->config;
    }

}