<?php


namespace WebHead;

use Nette;


/*
 * @author Adam Bisek
 */
class CompilerExtension extends Nette\Config\CompilerExtension
{

	const EXTENSION_NAME = 'webHead';


	private function getDefaultConfig()
	{
		return array(
			'compiler' => array(
				'outputDir' => '%wwwDir%/temp',
				'wwwPath' => '@' . self::EXTENSION_NAME . '.basePathFactory',
				'filters' => array(),
			),
			'control' => array(
				// all keys will be setted via control setters
			),
			'js' => array(
				// array of js files
			),
			'css' => array(
				// array of css files
			),
		);
	}


	public function loadConfiguration()
	{
		$config = $this->getConfig($this->getDefaultConfig());
		$builder = $this->getContainerBuilder();
		$builder->parameters[self::EXTENSION_NAME] = $config; // set params as public params
	}


	public function beforeCompile()
	{
		$builder = $this->getContainerBuilder();

		// install macro
		if(NETTE_VERSION_ID < 20100){ // lower than 2.1
			$latte = $builder->getDefinition('nette.latte');
			$latte->addSetup(__NAMESPACE__ . '\Macro::install(?->compiler)', '@self');
		}else{
			throw Nette\NotImplementedException("Nette 2.1.x is not supported yet."); // in 2.1.x will be section for macros registration
		}

		// create service definition
		$builder->getDefinition($this->name) // definition is DI\Nested Accesor, so override it with Control
			->setClass(__NAMESPACE__ . '\Control')
			->addSetup('setConfig', '%webHead%')
			->setShared(TRUE)
			->factory = NULL;

		// base path detection
		$builder->addDefinition($this->prefix('basePathFactory'))
			->setFactory(__NAMESPACE__ . '\Utils::getBasePath', array('@httpRequest', 'temp'))
			->setShared(FALSE);
	}


	public static function install(Nette\Config\Configurator $configurator)
	{
		$self = new static;
		$configurator->onCompile[] = function ($configurator, $compiler) use ($self) {
			$compiler->addExtension($self::EXTENSION_NAME, $self);
		};
	}

}
