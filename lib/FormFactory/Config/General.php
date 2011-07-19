<?php

namespace FormFactory\Config;


use FormFactory\Adapter;

/**
 *
 * General FormFactory configuration singleton object
 * @author ldavis
 *
 */
class General
{
    /**
     * Singleton instance
     * @var FormFactory\Config\General
     */
    protected static $_instance = null;

    /**
     * General config object
     * @var Zend_Config
     */
    protected $config;

    /**
     * Adapter used for form modelling
     * @var FormFactory\Adapter
     */
    protected $adapter;

    /**
     * Entity manager requires injecing when using Doctrine 2 adapter
     * @var \Doctrine\ORM\EntityManager
     */
    protected $manager;

    /**
     * Unable to construct from outside the object, this object is a singleton
     */
    private function __construct()
    {
    }

    public static function getInstance()
    {
        if (self::$_instance === null)
        {
            self::$_instance = new self();
        }
        return self::$_instance;
    }


    public function getConfig()
    {
        return $this->config;
    }

    public function setConfig($config)
    {
        $this->config = Helper::start($config)->getObject();
    }

    protected function setAdapter()
    {
        if (empty($this->config->ff->adapter->type))
        {
            throw new Exception('No adapter has been set in the passed configuration file');
        }

        switch(strtolower($this->config->ff->adapter->type))
        {
            case 'doctrine':
                $this->adapter = new Adapter\Doctrine();
                break;
            case 'doctrine2':
                $this->adapter = new Adapter\Doctrine2();
                break;
        }

        if ($this->adapter === null)
        {
            throw new Exception('Incorrect adapter name ' . $this->config->adapter);
        }
    }

    public function setManager($manager)
    {
        $this->manager = $manager;
    }

    public function getManager()
    {
        return $this->manager;
    }

    public function getAdapter()
    {
        if (is_null($this->adapter))
        {
            // Adapter is being called, and hasn't been set yet, lets set it
            $this->setAdapter();
        }

        if ($this->adapter instanceof Adapter\Doctrine2)
        {
            // Inject the entity manager before this adapter can be used
            $this->adapter->setEntitiyManager($this->manager);
        }
        return $this->adapter;
    }


    public function getNamespace()
    {
        if (isset($this->config->ff->namespace))
        {
            return $this->config->ff->namespace;
        }
    }

    /**
     *
     * Prefixes entity with any namespaces required
     * @param $entity
     * @return $className
     */
    public function getEntityClassName($entity)
    {
        $namespace = $this->getNamespace();
        if (!empty($namespace))
        {
            $className =  $namespace . '\\' . $entity;
        } else
        {
            $className = $entity;
        }
        return $className;
    }


    public function getConfigPath()
    {
        if (isset($this->config->ff->config->path))
        {
            return $this->config->ff->config->path;
        }
    }

    public function getConfigFileType()
    {
        if (isset($this->config->ff->config->filetype))
        {
            return $this->config->ff->config->filetype;
        }
    }
}
