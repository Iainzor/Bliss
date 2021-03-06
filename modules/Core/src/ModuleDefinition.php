<?php
namespace Core;

use Common\StringOperations;

class ModuleDefinition
{
	/**
	 * @var string
	 */
	private $namespace;
	
	/**
	 * @var string
	 */
	private $name;
	
	/**
	 * @var string
	 */
	private $rootDir;
	
	/**
	 * @var AbstractModule
	 */
	private $instance;
	
	/**
	 * @var ModuleConfig
	 */
	private $config;
	
	/**
	 * @var ControllerRegistry[]
	 */
	private $controllers = [];
	
	/**
	 * @var boolean
	 */
	private $initialized = false;
	
	/**
	 * Constructor
	 * 
	 * @param string $namespace
	 * @param string $rootDir
	 */
	public function __construct(string $namespace, string $rootDir)
	{
		$this->namespace = $namespace;
		$this->rootDir = $rootDir;
		$this->name = strtolower($namespace);
		$this->config = new ModuleConfig($this);
	}
	
	/**
	 * @return string
	 */
	public function getNamespace() : string
	{
		return $this->namespace;
	}
	
	/**
	 * Get the root directory of the module
	 * 
	 * @return string
	 */
	public function rootDir() : string
	{
		return $this->rootDir;
	}
	
	/**
	 * Get the name of the module.  Names are always the module's root directory name lowercased.
	 * 
	 * @return string
	 */
	public function name() : string 
	{
		return $this->name;
	}
	
	/**
	 * Get the module's configuration instance
	 * 
	 * @return \Core\ModuleConfig
	 */
	public function config() : ModuleConfig
	{
		return $this->config;
	}
	
	/**
	 * Get the module's instance
	 * 
	 * @param AbstractApplication $app
	 * @return mixed
	 * @throws \Exception
	 */
	public function instance(AbstractApplication $app)
	{
		$className = $this->namespace ."\\Module";
		
		if (!$this->instance && class_exists($className)) {
			$this->instance = $app->di()->create($className);
		}
		return $this->instance;
	}
	
	/**
	 * Get the definition for a controller within the module
	 * 
	 * @param string $controllerName
	 * @return ControllerDefinition
	 */
	public function controller($controllerName) : ControllerDefinition
	{
		$stringOps = new StringOperations();
		$name = $stringOps->camelize($controllerName);
		
		if (!isset($this->controllers[$name])) {
			$className = $this->namespace ."\\Controller\\". $name;
			$this->controllers[$name] = new ControllerDefinition($this, $className);
		}
		
		return $this->controllers[$name];
	}
	
	/**
	 * Initialize the module if it hasn't already
	 * 
	 * @param \Core\AbstractApplication $app
	 */
	public function initialize(AbstractApplication $app)
	{
		if (!$this->initialized) {
			$this->initialized = true;
			
			$instance = $this->instance($app);
			if ($instance) {
				$app->di()->register($instance);
			}
		}
	}
}