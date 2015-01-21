<?php

/**
 * This file is part of the Tracy (http://tracy.nette.org)
 * Copyright (c) 2004 David Grudl (http://davidgrudl.com)
 */

namespace Tracy\Bridges\DI;

use Nette;


/**
 * Tracy extension for Nette DI.
 *
 * @author     David Grudl
 */
class TracyExtension extends Nette\DI\CompilerExtension
{
	public $defaults = array(
		'email' => NULL,
		'editor' => NULL,
		'browser' => NULL,
		'errorTemplate' => NULL,
		'strictMode' => NULL,
		'maxLen' => NULL,
		'maxDepth' => NULL,
		'showLocation' => NULL,
		'scream' => NULL,
		'bar' => array(), // of class name
		'blueScreen' => array(), // of callback
	);

	/** @var bool */
	private $debugMode;


	public function __construct($debugMode = FALSE)
	{
		$this->debugMode = $debugMode;
	}


	public function loadConfiguration()
	{
		$container = $this->getContainerBuilder();

		$container->addDefinition($this->prefix('logger'))
			->setClass('Tracy\ILogger')
			->setFactory('Tracy\Debugger::getLogger');

		$container->addDefinition($this->prefix('blueScreen'))
			->setFactory('Tracy\Debugger::getBlueScreen');

		$container->addDefinition($this->prefix('bar'))
			->setFactory('Tracy\Debugger::getBar');
	}


	public function afterCompile(Nette\PhpGenerator\ClassType $class)
	{
		$initialize = $class->methods['initialize'];
		$container = $this->getContainerBuilder();
		$config = $options = $this->validateConfig($this->defaults);

		unset($options['bar'], $options['blueScreen']);
		foreach ($options as $key => $value) {
			$initialize->addBody('Tracy\Debugger::$? = ?;', array($key, $value));
		}

		if ($this->debugMode) {
			foreach ((array) $config['bar'] as $item) {
				$initialize->addBody($container->formatPhp(
					'$this->getService(?)->addPanel(?);',
					Nette\DI\Compiler::filterArguments(array(
						$this->prefix('bar'),
						is_string($item) ? new Nette\DI\Statement($item) : $item)
					)
				));
			}
		}

		foreach ((array) $config['blueScreen'] as $item) {
			$initialize->addBody($container->formatPhp(
				'$this->getService(?)->addPanel(?);',
				Nette\DI\Compiler::filterArguments(array($this->prefix('bluescreen'), $item))
			));
		}
	}

}
