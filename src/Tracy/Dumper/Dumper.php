<?php

/**
 * This file is part of the Tracy (https://tracy.nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Tracy;


/**
 * Dumps a variable.
 */
class Dumper
{
	public const
		DEPTH = 'depth', // how many nested levels of array/object properties display (defaults to 4)
		TRUNCATE = 'truncate', // how truncate long strings? (defaults to 150)
		COLLAPSE = 'collapse', // collapse top array/object or how big are collapsed? (defaults to 14)
		COLLAPSE_COUNT = 'collapsecount', // how big array/object are collapsed? (defaults to 7)
		LOCATION = 'location', // show location string? (defaults to 0)
		OBJECT_EXPORTERS = 'exporters', // custom exporters for objects (defaults to Dumper::$objectexporters)
		LAZY = 'lazy', // lazy-loading via JavaScript? true=full, false=none, null=collapsed parts (defaults to null/false)
		LIVE = 'live', // use static $liveSnapshot (used by Bar)
		SNAPSHOT = 'snapshot', // array used for shared snapshot for lazy-loading via JavaScript
		DEBUGINFO = 'debuginfo', // use magic method __debugInfo if exists (defaults to false)
		KEYS_TO_HIDE = 'keystohide'; // sensitive keys not displayed (defaults to [])

	public const
		LOCATION_SOURCE = 0b0001, // shows where dump was called
		LOCATION_LINK = 0b0011, // shows source and appends clickable anchor
		LOCATION_CLASS = 0b0100; // shows where class is defined

	public const
		HIDDEN_VALUE = '*****';

	/** @var array */
	public static $liveSnapshot = [];

	/** @var array */
	public static $terminalColors = [
		'bool' => '1;33',
		'null' => '1;33',
		'number' => '1;32',
		'string' => '1;36',
		'array' => '1;31',
		'key' => '1;37',
		'public' => '1;37',
		'protected' => '1;37',
		'private' => '1;37',
		'object' => '1;31',
		'resource' => '1;37',
		'indent' => '1;30',
	];

	/** @var array */
	public static $resources = [
		'stream' => 'stream_get_meta_data',
		'stream-context' => 'stream_context_get_options',
		'curl' => 'curl_getinfo',
	];

	/** @var array */
	public static $objectExporters = [
		'Closure' => [Exposer::class, 'exposeClosure'],
		'SplFileInfo' => [Exposer::class, 'exposeSplFileInfo'],
		'SplObjectStorage' => [Exposer::class, 'exposeSplObjectStorage'],
		'__PHP_Incomplete_Class' => [Exposer::class, 'exposePhpIncompleteClass'],
	];

	/** @var int */
	private $location = 0;

	/** @var Describer */
	private $describer;

	/** @var Renderer */
	private $renderer;


	/**
	 * Dumps variable to the output.
	 * @return mixed  variable
	 */
	public static function dump($var, array $options = [])
	{
		if (PHP_SAPI !== 'cli' && !preg_match('#^Content-Type: (?!text/html)#im', implode("\n", headers_list()))) {
			echo self::toHtml($var, $options);
		} elseif (self::detectColors()) {
			echo self::toTerminal($var, $options);
		} else {
			echo self::toText($var, $options);
		}
		return $var;
	}


	/**
	 * Dumps variable to HTML.
	 */
	public static function toHtml($var, array $options = []): string
	{
		return (new static($options))->asHtml($var);
	}


	/**
	 * Dumps variable to plain text.
	 */
	public static function toText($var, array $options = []): string
	{
		return (new static($options))->asTerminal($var);
	}


	/**
	 * Dumps variable to x-terminal.
	 */
	public static function toTerminal($var, array $options = []): string
	{
		return (new static($options))->asTerminal($var, self::$terminalColors);
	}


	private function __construct(array $options = [])
	{
		$tmp = $options[self::LOCATION] ?? $this->location;
		$this->location = $tmp === true ? ~0 : (int) $tmp;

		$describer = $this->describer = new Describer;
		$describer->maxDepth = $options[self::DEPTH] ?? $describer->maxDepth;
		$describer->maxLength = $options[self::TRUNCATE] ?? $describer->maxLength;
		$describer->location = ($this->location & self::LOCATION_CLASS) === self::LOCATION_CLASS;
		if ($options[self::LIVE] ?? false) {
			$describer->snapshot = &self::$liveSnapshot;
		} elseif (isset($options[self::SNAPSHOT])) {
			$describer->snapshot = &$options[self::SNAPSHOT];
		}
		$describer->debugInfo = $options[self::DEBUGINFO] ?? $describer->debugInfo;
		$describer->keysToHide = array_flip(array_map('strtolower', $options[self::KEYS_TO_HIDE] ?? []));
		$describer->resourceExposers = ($options['resourceExporters'] ?? []) + self::$resources;
		$describer->objectExposers = ($options[self::OBJECT_EXPORTERS] ?? []) + self::$objectExporters;

		$renderer = $this->renderer = new Renderer;
		$renderer->collapseTop = $options[self::COLLAPSE] ?? $renderer->collapseTop;
		$renderer->collapseSub = $options[self::COLLAPSE_COUNT] ?? $renderer->collapseSub;
		$renderer->locationLink = ($this->location & self::LOCATION_LINK) === self::LOCATION_LINK;
		$renderer->snapshot = &$describer->snapshot;
		$renderer->collectingMode = isset($options[self::SNAPSHOT]) || !empty($options[self::LIVE]);
		$renderer->lazy = $renderer->collectingMode ? true : ($options[self::LAZY] ?? $renderer->lazy);
	}


	/**
	 * Dumps variable to HTML.
	 */
	private function asHtml($var): string
	{
		$location = ($this->location & self::LOCATION_SOURCE) === self::LOCATION_SOURCE
			? self::findLocation()
			: null;
		$model = $this->describer->describe($var);
		return $this->renderer->renderHtml($model, $location);
	}


	/**
	 * Dumps variable to x-terminal.
	 */
	private function asTerminal($var, array $colors = []): string
	{
		$location = ($this->location & self::LOCATION_LINK) === self::LOCATION_LINK
			? self::findLocation()
			: null;
		$model = $this->describer->describe($var);
		return $this->renderer->renderText($model, $location, $colors);
	}


	public static function formatSnapshotAttribute(array &$snapshot): string
	{
		$res = $snapshot;
		foreach ($res as $obj) {
			unset($obj->depth, $obj->object);
		}
		$snapshot = [];
		return "'" . json_encode($res, JSON_HEX_APOS | JSON_HEX_AMP) . "'";
	}


	/**
	 * Finds the location where dump was called. Returns [file, line, code]
	 */
	private static function findLocation(): ?array
	{
		foreach (debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS) as $item) {
			if (isset($item['class']) && $item['class'] === __CLASS__) {
				$location = $item;
				continue;
			} elseif (isset($item['function'])) {
				try {
					$reflection = isset($item['class'])
						? new \ReflectionMethod($item['class'], $item['function'])
						: new \ReflectionFunction($item['function']);
					if ($reflection->isInternal() || preg_match('#\s@tracySkipLocation\s#', (string) $reflection->getDocComment())) {
						$location = $item;
						continue;
					}
				} catch (\ReflectionException $e) {
				}
			}
			break;
		}

		if (isset($location['file'], $location['line']) && is_file($location['file'])) {
			$lines = file($location['file']);
			$line = $lines[$location['line'] - 1];
			return [
				$location['file'],
				$location['line'],
				trim(preg_match('#\w*dump(er::\w+)?\(.*\)#i', $line, $m) ? $m[0] : $line),
			];
		}
		return null;
	}


	private static function detectColors(): bool
	{
		return self::$terminalColors &&
			(getenv('ConEmuANSI') === 'ON'
			|| getenv('ANSICON') !== false
			|| getenv('term') === 'xterm-256color'
			|| (defined('STDOUT') && function_exists('posix_isatty') && posix_isatty(STDOUT)));
	}
}
