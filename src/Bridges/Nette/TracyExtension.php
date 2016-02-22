<?php

/**
 * This file is part of the Tracy (https://tracy.nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

namespace Tracy\Bridges\Nette;

use Nette;
use Tracy;


/**
 * Tracy extension for Nette DI.
 */
class TracyExtension extends Nette\DI\CompilerExtension
{
	public $defaults = [
		'email' => NULL,
		'fromEmail' => NULL,
		'logSeverity' => NULL,
		'editor' => NULL,
		'browser' => NULL,
		'errorTemplate' => NULL,
		'strictMode' => NULL,
		'showBar' => NULL,
		'maxLen' => NULL,
		'maxDepth' => NULL,
		'showLocation' => NULL,
		'scream' => NULL,
		'bar' => [], // of class name
		'blueScreen' => [], // of callback
	];

	/** @var bool */
	private $debugMode;


	public function __construct($debugMode = FALSE)
	{
		$this->debugMode = $debugMode;
	}


	public function loadConfiguration()
	{
		$this->validateConfig($this->defaults);
		$container = $this->getContainerBuilder();

		$container->addDefinition($this->prefix('logger'))
			->setClass(Tracy\ILogger::class)
			->setFactory('Tracy\Debugger::getLogger');

		$container->addDefinition($this->prefix('blueScreen'))
			->setFactory('Tracy\Debugger::getBlueScreen');

		$container->addDefinition($this->prefix('bar'))
			->setFactory('Tracy\Debugger::getBar');
	}


	public function afterCompile(Nette\PhpGenerator\ClassType $class)
	{
		$initialize = $class->getMethod('initialize');
		$container = $this->getContainerBuilder();

		$options = $this->config;
		unset($options['bar'], $options['blueScreen']);
		foreach ($options as $key => $value) {
			if ($value !== NULL) {
				$key = ($key === 'fromEmail' ? 'getLogger()->' : '$') . $key;
				$initialize->addBody($container->formatPhp(
					'Tracy\Debugger::' . $key . ' = ?;',
					Nette\DI\Compiler::filterArguments([$value])
				));
			}
		}

		if ($this->debugMode) {
			foreach ((array) $this->config['bar'] as $item) {
				$initialize->addBody($container->formatPhp(
					'$this->getService(?)->addPanel(?);',
					Nette\DI\Compiler::filterArguments([
						$this->prefix('bar'),
						is_string($item) ? new Nette\DI\Statement($item) : $item,
					])
				));
			}
		}

		foreach ((array) $this->config['blueScreen'] as $item) {
			$initialize->addBody($container->formatPhp(
				'$this->getService(?)->addPanel(?);',
				Nette\DI\Compiler::filterArguments([$this->prefix('blueScreen'), $item])
			));
		}
	}

}
