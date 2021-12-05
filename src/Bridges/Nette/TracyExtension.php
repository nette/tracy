<?php

/**
 * This file is part of the Tracy (https://tracy.nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Tracy\Bridges\Nette;

use Nette;
use Nette\Schema\Expect;
use Nette\Utils\Strings;
use Tracy;


/**
 * Tracy extension for Nette DI.
 */
class TracyExtension extends Nette\DI\CompilerExtension
{
	private const ERROR_SEVERITY_CONSTANT_PATTERN = 'E_(?:ALL|PARSE|STRICT|RECOVERABLE_ERROR|(?:CORE|COMPILE)_(?:ERROR|WARNING)|(?:USER_)?(?:ERROR|WARNING|NOTICE|DEPRECATED))';
	private const ERROR_SEVERITY_EXPRESSION_PATTERN = '~?\s*' . self::ERROR_SEVERITY_CONSTANT_PATTERN . '(?:\s*[&|]\s*~?\s*' . self::ERROR_SEVERITY_CONSTANT_PATTERN . ')*';

	/** @var bool */
	private $debugMode;

	/** @var bool */
	private $cliMode;


	public function __construct(bool $debugMode = false, bool $cliMode = false)
	{
		$this->debugMode = $debugMode;
		$this->cliMode = $cliMode;
	}


	public function getConfigSchema(): Nette\Schema\Schema
	{
		$errorSeverity = Expect::anyOf(Expect::int(), Expect::string()->pattern(self::ERROR_SEVERITY_EXPRESSION_PATTERN));

		return Expect::structure([
			'email' => Expect::anyOf(Expect::email(), Expect::listOf('email'))->dynamic(),
			'fromEmail' => Expect::email()->dynamic(),
			'emailSnooze' => Expect::string()->dynamic(),
			'logSeverity' => Expect::anyOf($errorSeverity, Expect::listOf($errorSeverity)),
			'editor' => Expect::string()->dynamic(),
			'browser' => Expect::string()->dynamic(),
			'errorTemplate' => Expect::string()->dynamic(),
			'strictMode' => Expect::anyOf(Expect::bool()->dynamic(), $errorSeverity, Expect::listOf($errorSeverity)),
			'showBar' => Expect::bool()->dynamic(),
			'maxLength' => Expect::int()->dynamic(),
			'maxDepth' => Expect::int()->dynamic(),
			'keysToHide' => Expect::array(null)->dynamic(),
			'dumpTheme' => Expect::string()->dynamic(),
			'showLocation' => Expect::bool()->dynamic(),
			'scream' => Expect::anyOf(Expect::bool()->dynamic(), $errorSeverity, Expect::listOf($errorSeverity)),
			'bar' => Expect::listOf('string|Nette\DI\Definitions\Statement'),
			'blueScreen' => Expect::listOf('callable'),
			'editorMapping' => Expect::arrayOf('string')->dynamic()->default(null),
			'netteMailer' => Expect::bool(true),
		]);
	}


	public function loadConfiguration()
	{
		$builder = $this->getContainerBuilder();

		$builder->addDefinition($this->prefix('logger'))
			->setClass(Tracy\ILogger::class)
			->setFactory([Tracy\Debugger::class, 'getLogger']);

		$builder->addDefinition($this->prefix('blueScreen'))
			->setFactory([Tracy\Debugger::class, 'getBlueScreen']);

		$builder->addDefinition($this->prefix('bar'))
			->setFactory([Tracy\Debugger::class, 'getBar']);
	}


	public function afterCompile(Nette\PhpGenerator\ClassType $class)
	{
		$initialize = $this->initialization ?? new Nette\PhpGenerator\Closure;
		$initialize->addBody('if (!Tracy\Debugger::isEnabled()) { return; }');

		$builder = $this->getContainerBuilder();

		$options = (array) $this->config;
		unset($options['bar'], $options['blueScreen'], $options['netteMailer']);
		if (isset($options['logSeverity'])) {
			$options['logSeverity'] = $this->normalizeErrorSeverity($options['logSeverity']);
		}
		if (isset($options['strictMode']) && !is_bool($options['strictMode'])) {
			$options['strictMode'] = $this->normalizeErrorSeverity($options['strictMode']);
		}
		if (isset($options['scream']) && !is_bool($options['scream'])) {
			$options['scream'] = $this->normalizeErrorSeverity($options['scream']);
		}
		foreach ($options as $key => $value) {
			if ($value !== null) {
				static $tbl = [
					'keysToHide' => 'array_push(Tracy\Debugger::getBlueScreen()->keysToHide, ... ?)',
					'fromEmail' => 'Tracy\Debugger::getLogger()->fromEmail = ?',
					'emailSnooze' => 'Tracy\Debugger::getLogger()->emailSnooze = ?',
				];
				$initialize->addBody($builder->formatPhp(
					($tbl[$key] ?? 'Tracy\Debugger::$' . $key . ' = ?') . ';',
					Nette\DI\Helpers::filterArguments([$value])
				));
			}
		}

		$logger = $builder->getDefinition($this->prefix('logger'));
		if (
			!$logger instanceof Nette\DI\ServiceDefinition
			|| $logger->getFactory()->getEntity() !== [Tracy\Debugger::class, 'getLogger']
		) {
			$initialize->addBody($builder->formatPhp('Tracy\Debugger::setLogger(?);', [$logger]));
		}
		if ($this->config->netteMailer && $builder->getByType(Nette\Mail\IMailer::class)) {
			$initialize->addBody($builder->formatPhp('Tracy\Debugger::getLogger()->mailer = ?;', [
				[new Nette\DI\Statement(Tracy\Bridges\Nette\MailSender::class, ['fromEmail' => $this->config->fromEmail]), 'send'],
			]));
		}

		if ($this->debugMode) {
			foreach ($this->config->bar as $item) {
				if (is_string($item) && substr($item, 0, 1) === '@') {
					$item = new Nette\DI\Statement(['@' . $builder::THIS_CONTAINER, 'getService'], [substr($item, 1)]);
				} elseif (is_string($item)) {
					$item = new Nette\DI\Statement($item);
				}
				$initialize->addBody($builder->formatPhp(
					'$this->getService(?)->addPanel(?);',
					Nette\DI\Helpers::filterArguments([$this->prefix('bar'), $item])
				));
			}

			if (!$this->cliMode && ($name = $builder->getByType(Nette\Http\Session::class))) {
				$initialize->addBody('$this->getService(?)->start();', [$name]);
				$initialize->addBody('Tracy\Debugger::dispatch();');
			}
		}

		foreach ($this->config->blueScreen as $item) {
			$initialize->addBody($builder->formatPhp(
				'$this->getService(?)->addPanel(?);',
				Nette\DI\Helpers::filterArguments([$this->prefix('blueScreen'), $item])
			));
		}

		if (empty($this->initialization)) {
			$class->getMethod('initialize')->addBody("($initialize)();");
		}

		if (($dir = Tracy\Debugger::$logDirectory) && !is_writable($dir)) {
			throw new Nette\InvalidStateException("Make directory '$dir' writable.");
		}
	}


	/**
	 * @param int|string|array<int|string> $levels
	 * @return int
	 */
	private function normalizeErrorSeverity($levels): int
	{
		$result = 0;
		foreach ((array) $levels as $level) {
			$result |= is_int($level)
				? $level
				: $this->parseErrorSeverityExpression($level);
		}
		return $result;
	}


	private function parseErrorSeverityExpression(string $severityExpression): int
	{
		$orParts = Strings::split($severityExpression, '#\s*\|\s*#');
		if (count($orParts) !== 1) {
			return $this->normalizeErrorSeverity($orParts);
		}

		$result = null;
		foreach (Strings::split($orParts[0], '#\s*&\s*#') as $part) {
			[, $not, $constantName] = Strings::match($part, '#^(\s*~)?\s*(' . self::ERROR_SEVERITY_CONSTANT_PATTERN . ')$#');
			$value = constant($constantName);
			if ($not) {
				$value = ~$value;
			}
			$result = ($result ?? $value) & $value;
		}

		return $result;
	}
}
