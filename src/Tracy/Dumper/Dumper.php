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
		'object' => '1;31',
		'visibility' => '1;30',
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
		'Closure' => [self::class, 'exportClosure'],
		'SplFileInfo' => [self::class, 'exportSplFileInfo'],
		'SplObjectStorage' => [self::class, 'exportSplObjectStorage'],
		'__PHP_Incomplete_Class' => [self::class, 'exportPhpIncompleteClass'],
	];

	/** @var int|null */
	private $maxDepth = 4;

	/** @var int|null */
	private $maxLength = 150;

	/** @var int|bool */
	private $collapseTop = 14;

	/** @var int */
	private $collapseSub = 7;

	/** @var int */
	private $location = 0;

	/** @var bool|null  lazy-loading via JavaScript? true=full, false=none, null=collapsed parts */
	private $lazy;

	/** @var array|null */
	private $snapshot;

	/** @var bool */
	private $debugInfo = false;

	/** @var array */
	private $keysToHide = [];

	/** @var callable[] */
	private $resourceDumpers;

	/** @var callable[] */
	private $objectDumpers;

	/** @var array */
	private $parents = [];

	/** @var array|null */
	private $snapshotSelection;


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
		$this->maxDepth = $options[self::DEPTH] ?? $this->maxDepth;
		$this->maxLength = $options[self::TRUNCATE] ?? $this->maxLength;
		$this->collapseTop = $options[self::COLLAPSE] ?? $this->collapseTop;
		$this->collapseSub = $options[self::COLLAPSE_COUNT] ?? $this->collapseSub;
		$this->location = $options[self::LOCATION] ?? $this->location;
		$this->location = $this->location === true ? ~0 : (int) $this->location;
		$this->snapshot = &$options[self::SNAPSHOT];
		if ($options[self::LIVE] ?? false) {
			$this->snapshot = &self::$liveSnapshot;
		}
		$this->lazy = is_array($this->snapshot) ? true : ($options[self::LAZY] ?? $this->lazy);
		$this->debugInfo = $options[self::DEBUGINFO] ?? $this->debugInfo;
		$this->keysToHide = array_flip(array_map('strtolower', $options[self::KEYS_TO_HIDE] ?? []));
		$this->resourceDumpers = ($options['resourceExporters'] ?? []) + self::$resources;
		$this->objectDumpers = ($options[self::OBJECT_EXPORTERS] ?? []) + self::$objectExporters;
		uksort($this->objectDumpers, function ($a, $b): int {
			return $b === '' || (class_exists($a, false) && is_subclass_of($a, $b)) ? -1 : 1;
		});
	}


	/**
	 * Dumps variable to HTML.
	 */
	private function asHtml($var): string
	{
		[$file, $line, $code] = $this->location ? $this->findLocation() : null;
		$locAttrs = $file && $this->location & self::LOCATION_SOURCE ? Helpers::formatHtml(
			' title="%in file % on line %" data-tracy-href="%"', "$code\n", $file, $line, Helpers::editorUri($file, $line)
		) : null;

		$collectingMode = is_array($this->snapshot);
		$model = $this->toJson($var);

		if ($this->lazy === false) { // no lazy-loading
			$html = $this->dumpVar($model);
			$model = $snapshot = null;

		} elseif ($this->lazy && (is_array($var) && $var || is_object($var) || is_resource($var))) { // full lazy-loading
			$html = null;
			$snapshot = $collectingMode ? null : (array) $this->snapshot;

		} else { // lazy-loading of collapsed parts
			$html = $this->dumpVar($model);
			$snapshot = $this->snapshotSelection;
			$model = $this->snapshotSelection = null;
		}

		return '<pre class="tracy-dump' . ($model && $this->collapseTop === true ? ' tracy-collapsed' : '') . '"'
			. $locAttrs
			. ($snapshot === null ? '' : ' data-tracy-snapshot=' . $this->formatSnapshotAttribute($snapshot))
			. ($model ? " data-tracy-dump='" . json_encode($model, JSON_HEX_APOS | JSON_HEX_AMP) . "'>" : '>')
			. $html
			. ($file && ($this->location & self::LOCATION_LINK) === self::LOCATION_LINK ? '<small>in ' . Helpers::editorLink($file, $line) . '</small>' : '')
			. "</pre>\n";
	}


	/**
	 * Dumps variable to x-terminal.
	 */
	private function asTerminal($var, array $colors = []): string
	{
		$this->lazy = false;
		$model = $this->toJson($var);
		$s = $this->dumpVar($model);
		if ($colors) {
			$s = preg_replace_callback('#<span class="tracy-dump-(\w+)">|</span>#', function ($m) use ($colors): string {
				return "\033[" . (isset($m[1], $colors[$m[1]]) ? $colors[$m[1]] : '0') . 'm';
			}, $s);
		}
		$s = htmlspecialchars_decode(strip_tags($s), ENT_QUOTES);
		if (($this->location & self::LOCATION_LINK) === self::LOCATION_LINK && ([$file, $line] = $this->findLocation())) {
			$s .= "in $file:$line";
		}
		return $s;
	}


	/**
	 * Internal toHtml() dump implementation.
	 * @param  mixed  $var
	 */
	private function dumpVar(&$var, int $depth = 0): string
	{
		if (method_exists(__CLASS__, $m = 'dump' . gettype($var))) {
			return $this->$m($var, $depth);
		} else {
			return "<span>unknown type</span>\n";
		}
	}


	private function dumpNull(): string
	{
		return "<span class=\"tracy-dump-null\">null</span>\n";
	}


	private function dumpBoolean(&$var): string
	{
		return '<span class="tracy-dump-bool">' . ($var ? 'true' : 'false') . "</span>\n";
	}


	private function dumpInteger(&$var): string
	{
		return "<span class=\"tracy-dump-number\">$var</span>\n";
	}


	private function dumpDouble(&$var): string
	{
		return '<span class="tracy-dump-number">' . json_encode($var) . "</span>\n";
	}


	private function dumpString(&$var): string
	{
		return '<span class="tracy-dump-string">"'
			. Helpers::escapeHtml($var)
			. '"</span>' . (strlen($var) > 1 ? ' (' . strlen($var) . ')' : '') . "\n";
	}


	private function dumpObject($var, int $depth): string
	{
		switch (true) {
			case isset($var->object):
				return $this->renderObject($var, $depth);

			case isset($var->number):
				return '<span class="tracy-dump-number">' . Helpers::escapeHtml($var->number) . "</span>\n";

			case isset($var->key):
				return '<span>' . Helpers::escapeHtml($var->key) . "</span>\n";

			case isset($var->string):
				return '<span class="tracy-dump-string">"'
					. Helpers::escapeHtml($var->string)
					. '"</span>' . ($var->length > 1 ? ' (' . $var->length . ')' : '') . "\n";

			case isset($var->stop):
				return '<span class="tracy-dump-array">array</span> (' . $var->stop[0] . ') ' . ($var->stop[1] ? '[ <i>RECURSION</i> ]' : '[ ... ]') . "\n";

			case isset($var->resource):
				return $this->dumpResource($var, $depth);

			default:
				throw new \Exception('Unknown type');
		}
	}


	private function dumpArray($var, int $depth): string
	{
		$out = '<span class="tracy-dump-array">array</span> (';

		if (empty($var)) {
			return $out . ")\n";
		}

		$collapsed = $depth
			? count($var) >= $this->collapseSub
			: (is_int($this->collapseTop) ? count($var) >= $this->collapseTop : $this->collapseTop);

		$span = '<span class="tracy-toggle' . ($collapsed ? ' tracy-collapsed' : '') . '"';

		if ($collapsed && $this->lazy !== false) {
			$this->copySnapshot($var);
			return $span . " data-tracy-dump='"
				. json_encode($var, JSON_HEX_APOS | JSON_HEX_AMP) . "'>"
				. $out . count($var) . ")</span>\n";
		}

		$out = $span . '>' . $out . count($var) . ")</span>\n" . '<div' . ($collapsed ? ' class="tracy-collapsed"' : '') . '>';
		foreach ($var as [$k, $v]) {
			$out .= '<span class="tracy-dump-indent">   ' . str_repeat('|  ', $depth) . '</span>'
				. '<span class="tracy-dump-key">' . Helpers::escapeHtml($k) . '</span> => '
				. $this->dumpVar($v, $depth + 1);
		}

		return $out . '</div>';
	}


	private function renderObject($var, int $depth): string
	{
		$object = $this->snapshot[$var->object];

		$editorAttributes = '';
		if (isset($object->editor)) {
			$editorAttributes = Helpers::formatHtml(
				' title="Declared in file % on line %" data-tracy-href="%"',
				$object->editor->file,
				$object->editor->line,
				$object->editor->url
			);
		}

		$out = '<span class="tracy-dump-object"' . $editorAttributes . '>'
			. Helpers::escapeHtml($object->name)
			. '</span> <span class="tracy-dump-hash">#' . $var->object . '</span>';

		if (!isset($object->items)) {
			return $out . " { ... }\n";

		} elseif (!$object->items) {
			return $out . "\n";

		} elseif (in_array($var->object, $this->parents, true)) {
			return $out . " { <i>RECURSION</i> }\n";
		}

		$collapsed = $depth
			? count($object->items) >= $this->collapseSub
			: (is_int($this->collapseTop) ? count($object->items) >= $this->collapseTop : $this->collapseTop);

		$span = '<span class="tracy-toggle' . ($collapsed ? ' tracy-collapsed' : '') . '"';

		if ($collapsed && $this->lazy !== false) {
			$this->copySnapshot($var);
			return $span . " data-tracy-dump='"
				. json_encode($var, JSON_HEX_APOS | JSON_HEX_AMP)
				. "'>" . $out . "</span>\n";
		}

		$out = $span . '>' . $out . "</span>\n" . '<div' . ($collapsed ? ' class="tracy-collapsed"' : '') . '>';
		$this->parents[] = $var->object;
		foreach ($object->items as [$k, $v, $vis]) {
			$out .= '<span class="tracy-dump-indent">   ' . str_repeat('|  ', $depth) . '</span>'
				. '<span class="tracy-dump-key">' . Helpers::escapeHtml($k) . '</span>'
				. ($vis ? ' <span class="tracy-dump-visibility">' . ($vis === 1 ? 'protected' : 'private') . '</span>' : '')
				. ' => '
				. $this->dumpVar($v, $depth + 1);
		}
		array_pop($this->parents);
		return $out . '</div>';
	}


	private function dumpResource($var, int $depth): string
	{
		$resource = $this->snapshot[$var->resource];
		$out = '<span class="tracy-dump-resource">' . Helpers::escapeHtml($resource->name) . '</span> '
			. '<span class="tracy-dump-hash">#' . substr($var->resource, 1) . '</span>';
		if (isset($resource->items)) {
			$out = "<span class=\"tracy-toggle tracy-collapsed\">$out</span>\n<div class=\"tracy-collapsed\">";
			foreach ($resource->items as [$k, $v]) {
				$out .= '<span class="tracy-dump-indent">   ' . str_repeat('|  ', $depth) . '</span>'
					. '<span class="tracy-dump-key">' . Helpers::escapeHtml($k) . '</span> => ' . $this->dumpVar($v, $depth + 1);
			}
			return $out . '</div>';
		}
		return "$out\n";
	}


	private function copySnapshot($model): void
	{
		settype($this->snapshotSelection, 'array');
		if (is_array($model)) {
			foreach ($model as [$k, $v]) {
				$this->copySnapshot($v);
			}
		} elseif (isset($model->object)) {
			$object = $this->snapshotSelection[$model->object] = $this->snapshot[$model->object];
			if (!in_array($model->object, $this->parents, true)) {
				$this->parents[] = $model->object;
				foreach ($object->items ?? [] as [$k, $v]) {
					$this->copySnapshot($v);
				}
				array_pop($this->parents);
			}
		} elseif (isset($model->resource)) {
			$resource = $this->snapshotSelection[$model->resource] = $this->snapshot[$model->resource];
			foreach ($resource->items ?? [] as [$k, $v]) {
				$this->copySnapshot($v);
			}
		}
	}


	/**
	 * @return mixed
	 */
	private function toJson(&$var, int $depth = 0)
	{
		if (is_bool($var) || $var === null || is_int($var)) {
			return $var;

		} elseif (is_float($var)) {
			return is_finite($var)
				? (strpos($tmp = json_encode($var), '.') ? $var : (object) ['number' => "$tmp.0"])
				: (object) ['number' => (string) $var];

		} elseif (is_string($var)) {
			$s = $this->encodeString($var, $this->maxLength);
			if ($s === $var) {
				return $s;
			}
			return (object) ['string' => $s, 'length' => strlen($var)];

		} elseif (is_array($var)) {
			static $marker;
			if ($marker === null) {
				$marker = uniqid("\x00", true);
			}
			if (count($var) && (isset($var[$marker]) || $depth >= $this->maxDepth)) {
				return (object) ['stop' => [count($var) - isset($var[$marker]), isset($var[$marker])]];
			}
			$res = [];
			try {
				$var[$marker] = true;
				foreach ($var as $k => &$v) {
					if ($k !== $marker) {
						$res[] = [
							$this->encodeKey($k),
							is_string($k) && isset($this->keysToHide[strtolower($k)])
								? (object) ['key' => self::hideValue($v)]
								: $this->toJson($v, $depth + 1),
						];
					}
				}
			} finally {
				unset($var[$marker]);
			}
			return $res;

		} elseif (is_object($var)) {
			$id = spl_object_id($var);
			$obj = &$this->snapshot[$id];
			if ($obj && $obj->depth <= $depth) {
				return (object) ['object' => $id];
			}

			$obj = $obj ?: (object) [
				'name' => Helpers::getClass($var),
				'depth' => $depth,
				'object' => $var,
			];
			if (empty($obj->editor) && ($this->location & self::LOCATION_CLASS)) {
				$rc = $var instanceof \Closure ? new \ReflectionFunction($var) : new \ReflectionClass($var);
				if ($editor = $rc->getFileName() ? Helpers::editorUri($rc->getFileName(), $rc->getStartLine()) : null) {
					$obj->editor = (object) ['file' => $rc->getFileName(), 'line' => $rc->getStartLine(), 'url' => $editor];
				}
			}

			if ($depth < $this->maxDepth || !$this->maxDepth) {
				$obj->depth = $depth;
				$obj->items = [];

				foreach ($this->exportObject($var) as $k => $v) {
					$vis = 0;
					if (isset($k[0]) && $k[0] === "\x00") {
						$vis = $k[1] === '*' ? 1 : 2;
						$k = substr($k, strrpos($k, "\x00") + 1);
					}
					$obj->items[] = [
						$this->encodeKey($k),
						is_string($k) && isset($this->keysToHide[strtolower($k)])
							? (object) ['key' => self::hideValue($v)]
							: $this->toJson($v, $depth + 1),
						$vis,
					];
				}
			}
			return (object) ['object' => $id];

		} elseif (is_resource($var)) {
			$id = 'r' . (int) $var;
			$obj = &$this->snapshot[$id];
			if (!$obj) {
				$type = get_resource_type($var);
				$obj = (object) ['name' => $type . ' resource'];
				if (isset($this->resourceDumpers[$type])) {
					foreach (($this->resourceDumpers[$type])($var) as $k => $v) {
						$obj->items[] = [$k, $this->toJson($v, $depth + 1)];
					}
				}
			}
			return (object) ['resource' => $id];

		} else {
			return (object) ['type' => 'unknown type'];
		}
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
	 * @internal
	 */
	public static function encodeString(string $s, int $maxLength = null): string
	{
		if ($maxLength) {
			$s = self::truncateString($tmp = $s, $maxLength);
			$shortened = $s !== $tmp;
		}

		if (preg_match('#[^\x09\x0A\x0D\x20-\x7E\xA0-\x{10FFFF}]#u', $s) || preg_last_error()) { // is binary?
			static $table;
			if ($table === null) {
				foreach (array_merge(range("\x00", "\x1F"), range("\x7F", "\xFF")) as $ch) {
					$table[$ch] = '\x' . str_pad(dechex(ord($ch)), 2, '0', STR_PAD_LEFT);
				}
				$table['\\'] = '\\\\';
				$table["\r"] = '\r';
				$table["\n"] = '\n';
				$table["\t"] = '\t';
			}

			$s = strtr($s, $table);
		}

		return $s . (empty($shortened) ? '' : ' ... ');
	}


	/**
	 * @internal
	 */
	public static function truncateString(string $s, int $maxLength): string
	{
		if (!preg_match('##u', $s)) {
			$s = substr($s, 0, $maxLength); // not UTF-8
		} elseif (function_exists('mb_substr')) {
			$s = mb_substr($s, 0, $maxLength, 'UTF-8');
		} else {
			$i = $len = 0;
			while (isset($s[$i])) {
				if (($s[$i] < "\x80" || $s[$i] >= "\xC0") && (++$len > $maxLength)) {
					$s = substr($s, 0, $i);
					break;
				}
				$i++;
			}
		}
		return $s;
	}


	/**
	 * @param  int|string  $k
	 * @return int|string
	 */
	private function encodeKey($key)
	{
		return is_int($key) || (preg_match('#^[!\#$%&()*+,./0-9:;<=>?@A-Z[\]^_`a-z{|}~-]{1,50}$#D', $key) && !preg_match('#^true|false|null$#iD', $key))
			? $key
			: '"' . $this->encodeString($key, $this->maxLength) . '"';
	}


	private function exportObject(object $obj): array
	{
		foreach ($this->objectDumpers as $type => $dumper) {
			if (!$type || $obj instanceof $type) {
				return $dumper($obj);
			}
		}

		if ($this->debugInfo && method_exists($obj, '__debugInfo')) {
			return $obj->__debugInfo();
		}

		return (array) $obj;
	}


	private static function exportClosure(\Closure $obj): array
	{
		$rc = new \ReflectionFunction($obj);
		$res = [];
		foreach ($rc->getParameters() as $param) {
			$res[] = '$' . $param->getName();
		}
		return [
			'file' => $rc->getFileName(),
			'line' => $rc->getStartLine(),
			'variables' => $rc->getStaticVariables(),
			'parameters' => implode(', ', $res),
		];
	}


	private static function exportSplFileInfo(\SplFileInfo $obj): array
	{
		return ['path' => $obj->getPathname()];
	}


	private static function exportSplObjectStorage(\SplObjectStorage $obj): array
	{
		$res = [];
		foreach (clone $obj as $item) {
			$res[] = ['object' => $item, 'data' => $obj[$item]];
		}
		return $res;
	}


	private static function exportPhpIncompleteClass(\__PHP_Incomplete_Class $obj): array
	{
		$info = ['className' => null, 'private' => [], 'protected' => [], 'public' => []];
		foreach ((array) $obj as $name => $value) {
			if ($name === '__PHP_Incomplete_Class_Name') {
				$info['className'] = $value;
			} elseif (preg_match('#^\x0\*\x0(.+)$#D', $name, $m)) {
				$info['protected'][$m[1]] = $value;
			} elseif (preg_match('#^\x0(.+)\x0(.+)$#D', $name, $m)) {
				$info['private'][$m[1] . '::$' . $m[2]] = $value;
			} else {
				$info['public'][$name] = $value;
			}
		}
		return $info;
	}


	private static function hideValue($var): string
	{
		return self::HIDDEN_VALUE . ' (' . (is_object($var) ? Helpers::getClass($var) : gettype($var)) . ')';
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
