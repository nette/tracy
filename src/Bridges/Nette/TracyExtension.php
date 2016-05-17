<?php

/**
 * This file is part of the Tracy (https://tracy.nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

namespace Tracy\Bridges\Nette;

use Nette;


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
		$builder = $this->getContainerBuilder();

		$builder->addDefinition($this->prefix('logger'))
			->setClass('Tracy\ILogger')
			->setFactory('Tracy\Debugger::getLogger');

		$builder->addDefinition($this->prefix('blueScreen'))
			->setFactory('Tracy\Debugger::getBlueScreen');

		$builder->addDefinition($this->prefix('bar'))
			->setFactory('Tracy\Debugger::getBar');
	}


	public function afterCompile(Nette\PhpGenerator\ClassType $class)
	{
		$initialize = $class->getMethod('initialize');
		$builder = $this->getContainerBuilder();

		$options = $this->config;
		unset($options['bar'], $options['blueScreen']);
		foreach ($options as $key => $value) {
			if ($value !== NULL) {
				$key = ($key === 'fromEmail' ? 'getLogger()->' : '$') . $key;
				$initialize->addBody($builder->formatPhp(
					'Tracy\Debugger::' . $key . ' = ?;',
					Nette\DI\Compiler::filterArguments([$value])
				));
			}
		}

		if ($this->debugMode) {
			foreach ((array) $this->config['bar'] as $item) {
				$initialize->addBody($builder->formatPhp(
					'$this->getService(?)->addPanel(?);',
					Nette\DI\Compiler::filterArguments([
						$this->prefix('bar'),
						is_string($item) ? new Nette\DI\Statement($item) : $item,
					])
				));
			}
			$initialize->addBody('if ($tmp = $this->getByType("Nette\Http\Session", FALSE)) { $tmp->start(); Tracy\Debugger::dispatch(); };');
		}

		foreach ((array) $this->config['blueScreen'] as $item) {
			$initialize->addBody($builder->formatPhp(
				'$this->getService(?)->addPanel(?);',
				Nette\DI\Compiler::filterArguments([$this->prefix('blueScreen'), $item])
			));
		}
	}

}
