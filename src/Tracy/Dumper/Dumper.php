<?php

/**
 * This file is part of the Tracy (https://tracy.nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Tracy;

use Tracy\Dumper\Describer;
use Tracy\Dumper\Exposer;
use Tracy\Dumper\Renderer;


/**
 * Dumps a variable.
 */
class Dumper
{
	public const
		DEPTH = 'depth', // how many nested levels of array/object properties display (defaults to 4)
		TRUNCATE = 'truncate', // how truncate long strings? (defaults to 150)
		ITEMS = 'items', // how many items in array/object display? (defaults to 100)
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
		LOCATION_LINK = 0b0010, // appends clickable anchor
		LOCATION_CLASS = 0b0100; // shows where class is defined

	public const
		HIDDEN_VALUE = Describer::HIDDEN_VALUE;

	/** @var Dumper\Value[] */
	public static $liveSnapshot = [];

	/** @var array */
	public static $terminalColors = [
		'bool' => '1;33',
		'null' => '1;33',
		'number' => '1;32',
		'string' => '1;36',
		'array' => '1;31',
		'public' => '1;37',
		'protected' => '1;37',
		'private' => '1;37',
		'dynamic' => '1;37',
		'virtual' => '1;37',
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

	/** @var int  how many nested levels of array/object properties display by dump() */
	public static $maxDepth = 3;

	/** @var int  how long strings display by dump() */
	public static $maxLength = 150;

	/** @var bool display location by dump()? */
	public static $showLocation = false;

	/** @var bool use colors in console by dump()? */
	public static $useColors;

	/** @var Describer */
	private $describer;

	/** @var Renderer */
	private $renderer;


	/**
	 * Dumps variable to the output.
	 * @return mixed  variable
	 */
	public static function dump($var, array $options = null)
	{
		if (Debugger::$productionMode === true) {
			return $var;
		}

		$dumper = $options === null ? self::fromStatics() : new self($options);

		if (PHP_SAPI === 'cli' || PHP_SAPI === 'phpdbg') {
			if (self::$useColors === null) {
				self::$useColors = Helpers::detectColors();
			}
			echo $dumper->asTerminal($var, self::$useColors ? self::$terminalColors : []);

		} elseif (preg_match('#^Content-Type: (?!text/html)#im', implode("\n", headers_list()))) { // non-html
			echo $dumper->asTerminal($var);

		} else { // html
			echo $dumper->asHtml($var);
		}
		return $var;
	}


	private static function fromStatics(): self
	{
		return new self([
			self::DEPTH => Debugger::$maxDepth ?? self::$maxDepth,
			self::TRUNCATE => Debugger::$maxLength ?? self::$maxLength,
			self::LOCATION => Debugger::$showLocation ?? self::$showLocation,
		]);
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
		$location = $options[self::LOCATION] ?? 0;
		$location = $location === true ? ~0 : (int) $location;

		$describer = $this->describer = new Describer;
		$describer->maxDepth = $options[self::DEPTH] ?? $describer->maxDepth;
		$describer->maxLength = $options[self::TRUNCATE] ?? $describer->maxLength;
		$describer->maxItems = $options[self::ITEMS] ?? $describer->maxItems;
		if ($options[self::LIVE] ?? false) {
			$describer->snapshot = &self::$liveSnapshot;
		} elseif (isset($options[self::SNAPSHOT])) {
			$describer->snapshot = &$options[self::SNAPSHOT];
		}
		$describer->debugInfo = $options[self::DEBUGINFO] ?? $describer->debugInfo;
		$describer->keysToHide = array_flip(array_map('strtolower', $options[self::KEYS_TO_HIDE] ?? []));
		$describer->resourceExposers = ($options['resourceExporters'] ?? []) + self::$resources;
		$describer->objectExposers = ($options[self::OBJECT_EXPORTERS] ?? []) + self::$objectExporters;
		$describer->showLocation = (bool) $location;

		$renderer = $this->renderer = new Renderer;
		$renderer->collapseTop = $options[self::COLLAPSE] ?? $renderer->collapseTop;
		$renderer->collapseSub = $options[self::COLLAPSE_COUNT] ?? $renderer->collapseSub;
		$renderer->collectingMode = isset($options[self::SNAPSHOT]) || !empty($options[self::LIVE]);
		$renderer->lazy = $renderer->collectingMode ? true : ($options[self::LAZY] ?? $renderer->lazy);
		$renderer->locationLink = !(~$location & self::LOCATION_LINK);
		$renderer->locationSource = !(~$location & self::LOCATION_SOURCE);
		$renderer->locationClass = !(~$location & self::LOCATION_CLASS);
	}


	/**
	 * Dumps variable to HTML.
	 */
	private function asHtml($var): string
	{
		$model = $this->describer->describe($var);
		return $this->renderer->renderAsHtml($model);
	}


	/**
	 * Dumps variable to x-terminal.
	 */
	private function asTerminal($var, array $colors = []): string
	{
		$model = $this->describer->describe($var);
		return $this->renderer->renderAsText($model, $colors);
	}


	public static function formatSnapshotAttribute(array &$snapshot): string
	{
		$res = Renderer::formatSnapshotAttribute($snapshot);
		$snapshot = [];
		return $res;
	}
}
