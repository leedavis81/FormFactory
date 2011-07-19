<?php
namespace FormFactory;

class ClassLoader
{
    protected $namespace;
    protected $includePath = '';

    public function __construct($namespace, $includePath)
    {
        $this->namespace = $namespace;
        $this->includePath = $includePath;
    }

    // Register this classloader object
    public function register()
    {
        spl_autoload_register(array($this, 'loadClass'));
    }


    // Load the required class
    public function loadClass($className)
    {
        // Make sure we loading our defined NS classes
        if($this->namespace !== null && strpos($className, $this->namespace . '\\') !== 0)
        {
            return false;
        }

        require $this->includePath . DIRECTORY_SEPARATOR . str_replace('\\', DIRECTORY_SEPARATOR, $className) . '.php';

        return true;
    }

}

$loader = new ClassLoader('FormFactory', realpath(__DIR__ . '/../'));
$loader->register();

$loader = new ClassLoader('FormFactory\Adapter', realpath(__DIR__ . '/../FormFactory'));
$loader->register();
$loader = new ClassLoader('FormFactory\Config', realpath(__DIR__ . '/../FormFactory'));
$loader->register();
$loader = new ClassLoader('FormFactory\Element', realpath(__DIR__ . '/../FormFactory'));
$loader->register();
$loader = new ClassLoader('FormFactory\Form', realpath(__DIR__ . '/../FormFactory'));
$loader->register();