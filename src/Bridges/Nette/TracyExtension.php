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
		'email' => null,
		'fromEmail' => null,
		'logSeverity' => null,
		'editor' => null,
		'browser' => null,
		'errorTemplate' => null,
		'strictMode' => null,
		'showBar' => null,
		'maxLen' => null,
		'maxLength' => null,
		'maxDepth' => null,
		'showLocation' => null,
		'scream' => null,
		'bar' => [], // of class name
		'blueScreen' => [], // of callback
		'editorMapping' => [],
		'netteMailer' => true,
	];

	/** @var bool */
	private $debugMode;

	/** @var bool */
	private $cliMode;


	public function __construct($debugMode = false, $cliMode = false)
	{
		$this->debugMode = $debugMode;
		$this->cliMode = $cliMode;
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
		$class = method_exists('Nette\DI\Helpers', 'filterArguments') ? 'Nette\DI\Helpers' : 'Nette\DI\Compiler';

		$options = $this->config;
		unset($options['bar'], $options['blueScreen'], $options['netteMailer']);
		if (isset($options['logSeverity'])) {
			$res = 0;
			foreach ((array) $options['logSeverity'] as $level) {
				$res |= is_int($level) ? $level : constant($level);
			}
			$options['logSeverity'] = $res;
		}
		foreach ($options as $key => $value) {
			if ($value !== null) {
				$key = ($key === 'fromEmail' ? 'getLogger()->' : '$') . $key;
				$initialize->addBody($builder->formatPhp(
					'Tracy\Debugger::' . $key . ' = ?;',
					$class::filterArguments([$value])
				));
			}
		}

		$logger = $builder->getDefinition($this->prefix('logger'));
		if ($logger->getFactory()->getEntity() !== ['Tracy\Debugger', 'getLogger']) {
			$initialize->addBody($builder->formatPhp('Tracy\Debugger::setLogger(?);', [$logger]));
		}
		if ($this->config['netteMailer'] && $builder->getByType('Nette\Mail\IMailer')) {
			$initialize->addBody($builder->formatPhp('Tracy\Debugger::getLogger()->mailer = ?;', [
				[new Nette\DI\Statement('Tracy\Bridges\Nette\MailSender', ['fromEmail' => $this->config['fromEmail']]), 'send'],
			]));
		}

		if ($this->debugMode) {
			foreach ((array) $this->config['bar'] as $item) {
				if (is_string($item) && substr($item, 0, 1) === '@') {
					$item = new Nette\DI\Statement(['@' . $builder::THIS_CONTAINER, 'getService'], [substr($item, 1)]);
				} elseif (is_string($item)) {
					$item = new Nette\DI\Statement($item);
				}
				$initialize->addBody($builder->formatPhp(
					'$this->getService(?)->addPanel(?);',
					$class::filterArguments([$this->prefix('bar'), $item])
				));
			}

			if (!$this->cliMode && ($name = $builder->getByType('Nette\Http\Session'))) {
				$initialize->addBody('$this->getService(?)->start();', [$name]);
				$initialize->addBody('Tracy\Debugger::dispatch();');
			}
		}

		foreach ((array) $this->config['blueScreen'] as $item) {
			$initialize->addBody($builder->formatPhp(
				'$this->getService(?)->addPanel(?);',
				$class::filterArguments([$this->prefix('blueScreen'), $item])
			));
		}

		if (($dir = Tracy\Debugger::$logDirectory) && !is_writable($dir)) {
			throw new Nette\InvalidStateException("Make directory '$dir' writable.");
		}
	}
}
