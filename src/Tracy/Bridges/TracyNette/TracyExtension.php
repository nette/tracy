<?php

/**
 * This file is part of the Tracy (http://tracy.nette.org)
 * Copyright (c) 2004 David Grudl (http://davidgrudl.com)
 */

namespace Tracy\Bridges\TracyNette;

use Nette,
	Nette\DI\ContainerBuilder;


/**
 * Tracy extendsion for Nette DI.
 *
 * @author     David Grudl
 */
class TracyExtension extends Nette\DI\CompilerExtension
{

	public $defaults = array(
		'email' => NULL,
		'editor' => NULL,
		'browser' => NULL,
		'strictMode' => NULL,
		'maxLen' => NULL,
		'maxDepth' => NULL,
		'showLocation' => NULL,
		'scream' => NULL,
		'bar' => array(), // of class name
		'blueScreen' => array(), // of callbacks
	);

	/** @var array */
	private $config;


	public function loadConfiguration()
	{
		$container = $this->getContainerBuilder();

		$config = $this->compiler->getConfig();
		if ($oldSection = !isset($config[$this->name]) && isset($config['nette']['debugger'])) {
			trigger_error("Configuration section 'nette.debugger' is deprecated, use section '$this->name' instead.", E_USER_DEPRECATED);
			$config = $this->config = Nette\DI\Config\Helpers::merge($config['nette']['debugger'], $this->defaults);
		} else {
			$config = $this->config = $this->getConfig($this->defaults);
		}

		$this->validate($config, $this->defaults,  $oldSection ? 'nette.debugger' : $this->name);

		$container->addDefinition('nette.logger')
			->setClass('Tracy\ILogger')
			->setFactory('Tracy\Debugger::getLogger');

		$container->addDefinition('nette.blueScreen')
			->setFactory('Tracy\Debugger::getBlueScreen');

		$container->addDefinition('nette.bar')
			->setFactory('Tracy\Debugger::getBar');
	}


	public function afterCompile(Nette\PhpGenerator\ClassType $class)
	{
		$initialize = $class->methods['initialize'];
		$container = $this->getContainerBuilder();
		$config = $this->config;

		$initialize->addBody('Tracy\Bridges\TracyNette\NetteBridge::initialize();');

		foreach (array('email', 'editor', 'browser', 'strictMode', 'maxLen', 'maxDepth', 'showLocation', 'scream') as $key) {
			if (isset($config[$key])) {
				$initialize->addBody('Tracy\Debugger::$? = ?;', array($key, $config[$key]));
			}
		}

		if ($container->parameters['debugMode']) {
			foreach ((array) $config['bar'] as $item) {
				$initialize->addBody($container->formatPhp(
						'$this->getService(?)->addPanel(?);',
						Nette\DI\Compiler::filterArguments(array('nette.bar', is_string($item) ? new Nette\DI\Statement($item) : $item))
					));
			}
		}

		foreach ((array) $config['blueScreen'] as $item) {
			$initialize->addBody($container->formatPhp(
					'$this->getService(?)->addPanel(?);',
					Nette\DI\Compiler::filterArguments(array('nette.blueScreen', $item))
				));
		}
	}


	private function validate(array $config, array $expected, $name)
	{
		if ($extra = array_diff_key($config, $expected)) {
			$extra = implode(", $name.", array_keys($extra));
			throw new Nette\InvalidStateException("Unknown option $name.$extra.");
		}
	}

}
