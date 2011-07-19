<?php

namespace FormFactory\Config;

/**
 *
 * helper to handle config files, returns Zend_Config object after testing path / file details
 * @author ldavis
 *
 */
class Helper
{
    /**
     *
     * Zend_Config object
     * @var Zend_Config
     */
    protected $config;

    /**
     * string for the required config section
     * @var string
     */
    protected $section = null;


    public function __construct($config = null, $section = null)
    {
        if ($section !== null)
        {
            $this->setSection($section);
        }
        if ($config !== null)
        {
            $this->setConfig($config);
        }
    }

    /**
     *
     * Factory call, allows method chaining
     * @param unknown_type $config
     */
    public static function start($config = null, $section = null)
    {
        $configObject = new self($config, $section);
        return $configObject;
    }

    /**
     *
     * Set config path|object|array
     * @param unknown_type $config
     */
    public function setConfig($config)
    {
        if (is_string($config))
		{
			$config = $this->loadConfig($config);
		} elseif ($config instanceof \Zend_Config)
		{
			$this->config = $config;
		} elseif (is_array($config))
		{
		    $this->config = new \Zend_Config($config);
		} else
		{
		    throw new Exception('Invalid options provided; must be location of config file, a config object, or an array');
		}
    }

    /**
     *
     * Returns a config object from a defined config resource
     * @return object
     */
    public function getObject()
    {
        if (!$this->config instanceof \Zend_Config)
        {
            throw new Exception('Configuration has not been set');
        }
        return $this->config;
    }


    /**
     *
     * Returns an array from a defined config resource
     * @return array
     */
    public function getArray()
    {
        if (!$this->config instanceof \Zend_Config)
        {
            throw new Exception('Configuration has not been set, array cannot be returned');
        }
        return $this->config->toArray();
    }


    /**
     * Load configuration file from a path
     *
     * @param  string $file
     * @throws Exception When invalid configuration file is provided
     * @return array
     */
    protected function loadConfig($file)
    {
        $suffix = pathinfo($file, PATHINFO_EXTENSION);

        switch (strtolower($suffix))
        {
            case 'ini':
            	require_once realpath(APPLICATION_PATH . '/../library/Zend/Config/Ini.php');
                $this->config = new \Zend_Config_Ini($file, $this->getSection());
                break;

            case 'xml':
            	require_once realpath(APPLICATION_PATH . '/../library/Zend/Config/Xml.php');
                $this->config = new \Zend_Config_Xml($file, $this->getSection());
                break;

            case 'json':
            	require_once realpath(APPLICATION_PATH . '/../library/Zend/Config/Json.php');
                $this->config = new \Zend_Config_Json($file, $this->getSection());
                break;

            case 'yaml':
            	require_once realpath(APPLICATION_PATH . '/../library/Zend/Config/Yaml.php');
                $this->config = new \Zend_Config_Yaml($file, $this->getSection());
                break;

            case 'php':
            case 'inc':
                $this->config = include $file;
                if (!is_array($this->config))
                {
                    throw new Exception('Invalid configuration file provided; PHP file does not return array value');
                }
                break;

            default:
                throw new Exception('Invalid configuration file provided; unknown config type');
        }
    }

    public function setSection($section)
    {
        $this->section = $section;
    }

    public function getSection()
    {
        return $this->section;
    }
}