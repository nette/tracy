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
		DEPTH = 'depth', // how many nested levels of array/object properties display (defaults to 7)
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
		KEYS_TO_HIDE = 'keystohide', // sensitive keys not displayed (defaults to [])
		THEME = 'theme'; // color theme (defaults to light)

	public const
		LOCATION_CLASS = 0b0001, // shows where classes are defined
		LOCATION_SOURCE = 0b0011, // additionally shows where dump was called
		LOCATION_LINK = self::LOCATION_SOURCE; // deprecated

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
		\Closure::class => [Exposer::class, 'exposeClosure'],
		\ArrayObject::class => [Exposer::class, 'exposeArrayObject'],
		\SplFileInfo::class => [Exposer::class, 'exposeSplFileInfo'],
		\SplObjectStorage::class => [Exposer::class, 'exposeSplObjectStorage'],
		\__PHP_Incomplete_Class::class => [Exposer::class, 'exposePhpIncompleteClass'],
		\DOMNode::class => [Exposer::class, 'exposeDOMNode'],
		\DOMNodeList::class => [Exposer::class, 'exposeDOMNodeList'],
		\DOMNamedNodeMap::class => [Exposer::class, 'exposeDOMNodeList'],
	];

	/** @var int  how many nested levels of array/object properties display by dump() */
	public static $maxDepth = 7;

	/** @var int  how long strings display by dump() */
	public static $maxLength = 150;

	/** @var int  how many items in array/object display by dump() */
	public static $maxItems = 100;

	/** @var bool display location by dump()? */
	public static $showLocation;

	/** @var array  sensitive keys not displayed by dump() */
	public static $keysToHide = [];

	/** @var bool use colors in console by dump()? */
	public static $useColors;

	/** @var string  theme used by dump() */
	public static $theme = 'light';

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
		if (Debugger::$productionMode === true) {
			return $var;
		}

		$options += self::fromStatics();

		if (PHP_SAPI === 'cli' || PHP_SAPI === 'phpdbg') {
			if (self::$useColors === null) {
				self::$useColors = Helpers::detectColors();
			}
			$dumper = new self($options);
			fwrite(STDOUT, $dumper->asTerminal($var, self::$useColors ? self::$terminalColors : []));

		} elseif (preg_match('#^Content-Type: (?!text/html)#im', implode("\n", headers_list()))) { // non-html
			$dumper = new self($options);
			echo $dumper->asTerminal($var);

		} else { // html
			$options[self::LOCATION] = $options[self::LOCATION] ?? true;
			self::renderAssets();
			$dumper = new self($options);
			echo $dumper->asHtml($var);
		}
		return $var;
	}


	private static function fromStatics(): array
	{
		return [
			self::DEPTH => Debugger::$maxDepth ?? self::$maxDepth,
			self::TRUNCATE => Debugger::$maxLength ?? self::$maxLength,
			self::ITEMS => self::$maxItems,
			self::LOCATION => Debugger::$showLocation ?? self::$showLocation,
			self::KEYS_TO_HIDE => self::$keysToHide,
			self::THEME => self::$theme,
		];
	}


	/**
	 * Dumps variable to HTML.
	 */
	public static function toHtml($var, array $options = []): string
	{
		return (new self($options))->asHtml($var);
	}


	/**
	 * Dumps variable to plain text.
	 */
	public static function toText($var, array $options = []): string
	{
		return (new self($options))->asTerminal($var);
	}


	/**
	 * Dumps variable to x-terminal.
	 */
	public static function toTerminal($var, array $options = []): string
	{
		return (new self($options))->asTerminal($var, self::$terminalColors);
	}


	/**
	 * Renders <script> & <style>
	 */
	public static function renderAssets(): void
	{
		static $sent;
		if (Debugger::$productionMode === true || $sent) {
			return;
		}
		$sent = true;

		$nonce = Helpers::getNonce();
		$nonceAttr = $nonce ? ' nonce="' . Helpers::escapeHtml($nonce) . '"' : '';
		$s = file_get_contents(__DIR__ . '/../Toggle/toggle.css')
			. file_get_contents(__DIR__ . '/assets/dumper-light.css')
			. file_get_contents(__DIR__ . '/assets/dumper-dark.css');
		echo "<style{$nonceAttr}>", str_replace('</', '<\/', Helpers::minifyCss($s)), "</style>\n";

		if (!Debugger::isEnabled()) {
			$s = '(function(){' . file_get_contents(__DIR__ . '/../Toggle/toggle.js') . '})();'
				. '(function(){' . file_get_contents(__DIR__ . '/../Dumper/assets/dumper.js') . '})();';
			echo "<script{$nonceAttr}>", str_replace(['<!--', '</s'], ['<\!--', '<\/s'], Helpers::minifyJs($s)), "</script>\n";
		}
	}


	private function __construct(array $options = [])
	{
		$location = $options[self::LOCATION] ?? 0;
		$location = $location === true ? ~0 : (int) $location;

		$describer = $this->describer = new Describer;
		$describer->maxDepth = $options[self::DEPTH] ?? $describer->maxDepth;
		$describer->maxLength = $options[self::TRUNCATE] ?? $describer->maxLength;
		$describer->maxItems = $options[self::ITEMS] ?? $describer->maxItems;
		$describer->debugInfo = $options[self::DEBUGINFO] ?? $describer->debugInfo;
		$describer->keysToHide = array_flip(array_map('strtolower', $options[self::KEYS_TO_HIDE] ?? []));
		$describer->resourceExposers = ($options['resourceExporters'] ?? []) + self::$resources;
		$describer->objectExposers = ($options[self::OBJECT_EXPORTERS] ?? []) + self::$objectExporters;
		$describer->location = (bool) $location;
		if ($options[self::LIVE] ?? false) {
			$tmp = &self::$liveSnapshot;
		} elseif (isset($options[self::SNAPSHOT])) {
			$tmp = &$options[self::SNAPSHOT];
		}
		if (isset($tmp)) {
			$tmp[0] = $tmp[0] ?? [];
			$tmp[1] = $tmp[1] ?? [];
			$describer->snapshot = &$tmp[0];
			$describer->references = &$tmp[1];
		}

		$renderer = $this->renderer = new Renderer;
		$renderer->collapseTop = $options[self::COLLAPSE] ?? $renderer->collapseTop;
		$renderer->collapseSub = $options[self::COLLAPSE_COUNT] ?? $renderer->collapseSub;
		$renderer->collectingMode = isset($options[self::SNAPSHOT]) || !empty($options[self::LIVE]);
		$renderer->lazy = $renderer->collectingMode ? true : ($options[self::LAZY] ?? $renderer->lazy);
		$renderer->sourceLocation = !(~$location & self::LOCATION_SOURCE);
		$renderer->classLocation = !(~$location & self::LOCATION_CLASS);
		$renderer->theme = $options[self::THEME] ?? $renderer->theme;
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
		$res = "'" . Renderer::jsonEncode($snapshot[0] ?? []) . "'";
		$snapshot = [];
		return $res;
	}
}
