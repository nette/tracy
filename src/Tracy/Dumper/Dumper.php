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
		LOCATION_LINK = 0b0010, // appends clickable anchor
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
		$this->lazy = is_array($this->snapshot)
			? true
			: ($options[self::LAZY] ?? $this->lazy);
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
			' title="%in file % on line %" data-tracy-href="%"',
			"$code\n",
			$file,
			$line,
			Helpers::editorUri($file, $line)
		) : null;

		if (is_array($this->snapshot)) {
			$options[self::SNAPSHOT] = &$this->snapshot;
		}
		$snapshot = &$options[self::SNAPSHOT]; // reference must exist

		$html = $json = null;
		if ($this->lazy && (is_array($var) || is_object($var) || is_resource($var)) && $var) {
			$json = $this->toJson($var, $options);
			$snapshot = (array) $snapshot;
		} else {
			$html = $this->dumpVar($var, $options + [self::LAZY => $this->lazy]);
		}

		return '<pre class="tracy-dump' . ($json && $this->collapseTop === true ? ' tracy-collapsed' : '') . '"'
			. $locAttrs
			. (is_array($snapshot) && !is_array($this->snapshot) ? ' data-tracy-snapshot=' . $this->formatSnapshotAttribute($snapshot) : '')
			. ($json ? " data-tracy-dump='" . json_encode($json, JSON_HEX_APOS | JSON_HEX_AMP) . "'>" : '>')
			. $html
			. ($file && $this->location & self::LOCATION_LINK ? '<small>in ' . Helpers::editorLink($file, $line) . '</small>' : '')
			. "</pre>\n";
	}


	/**
	 * Dumps variable to x-terminal.
	 */
	private function asTerminal($var, array $colors = []): string
	{
		$s = $this->dumpVar($var, [self::LAZY => false]);
		if ($colors) {
			$s = preg_replace_callback('#<span class="tracy-dump-(\w+)">|</span>#', function ($m) use ($colors): string {
				return "\033[" . (isset($m[1], $colors[$m[1]]) ? $colors[$m[1]] : '0') . 'm';
			}, $s);
		}
		$s = htmlspecialchars_decode(strip_tags($s), ENT_QUOTES);
		if ($this->location & self::LOCATION_LINK && ([$file, $line] = $this->findLocation())) {
			$s .= "in $file:$line";
		}
		return $s;
	}


	/**
	 * Internal toHtml() dump implementation.
	 * @param  mixed  $var
	 */
	private function dumpVar(&$var, array $options, int $level = 0): string
	{
		if (!method_exists(self::class, $m = 'dump' . explode(' ', gettype($var))[0])) {
			$m = 'dumpResource'; // closed resource is 'unknown type' in PHP 7.1
		}
		return $this->$m($var, $options, $level);
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
		$var = is_finite($var)
			? ($tmp = json_encode($var)) . (strpos($tmp, '.') === false ? '.0' : '')
			: var_export($var, true);
		return "<span class=\"tracy-dump-number\">$var</span>\n";
	}


	private function dumpString(&$var): string
	{
		return '<span class="tracy-dump-string">"'
			. Helpers::escapeHtml($this->encodeString($var, $this->maxLength))
			. '"</span>' . (strlen($var) > 1 ? ' (' . strlen($var) . ')' : '') . "\n";
	}


	private function dumpArray(&$var, array $options, int $level): string
	{
		static $marker;
		if ($marker === null) {
			$marker = uniqid("\x00", true);
		}

		$out = '<span class="tracy-dump-array">array</span> (';

		if (empty($var)) {
			return $out . ")\n";

		} elseif (isset($var[$marker])) {
			return $out . (count($var) - 1) . ") [ <i>RECURSION</i> ]\n";

		} elseif (!$this->maxDepth || $level < $this->maxDepth) {
			$collapsed = $level
				? count($var) >= $this->collapseSub
				: (is_int($this->collapseTop) ? count($var) >= $this->collapseTop : $this->collapseTop);

			$span = '<span class="tracy-toggle' . ($collapsed ? ' tracy-collapsed' : '') . '"';

			if ($collapsed && $options[self::LAZY] !== false) {
				$options[self::SNAPSHOT] = (array) $options[self::SNAPSHOT];
				return $span . " data-tracy-dump='"
					. json_encode($this->toJson($var, $options, $level), JSON_HEX_APOS | JSON_HEX_AMP) . "'>"
					. $out . count($var) . ")</span>\n";

			} else {
				$out = $span . '>' . $out . count($var) . ")</span>\n" . '<div' . ($collapsed ? ' class="tracy-collapsed"' : '') . '>';
				try {
					$var[$marker] = true;
					foreach ($var as $k => &$v) {
						if ($k === $marker) {
							continue;
						}
						$hide = is_string($k) && isset($this->keysToHide[strtolower($k)]);
						$out .= '<span class="tracy-dump-indent">   ' . str_repeat('|  ', $level) . '</span>'
						. '<span class="tracy-dump-key">' . Helpers::escapeHtml($this->encodeKey($k)) . '</span> => '
						. ($hide
							? Helpers::escapeHtml(self::hideValue($v)) . "\n"
							: $this->dumpVar($v, $options, $level + 1)
						);
					}
				} finally {
					unset($var[$marker]);
				}

				return $out . '</div>';
			}

		} else {
			return $out . count($var) . ") [ ... ]\n";
		}
	}


	private function dumpObject(&$var, array $options, int $level): string
	{
		$fields = $this->exportObject($var);

		$editorAttributes = '';
		if ($this->location & self::LOCATION_CLASS) {
			$rc = $var instanceof \Closure
				? new \ReflectionFunction($var)
				: new \ReflectionClass($var);
			$editor = $rc->getFileName()
				? Helpers::editorUri($rc->getFileName(), $rc->getStartLine())
				: null;
			if ($editor) {
				$editorAttributes = Helpers::formatHtml(
					' title="Declared in file % on line %" data-tracy-href="%"',
					$rc->getFileName(),
					$rc->getStartLine(),
					$editor
				);
			}
		}
		$out = '<span class="tracy-dump-object"' . $editorAttributes . '>'
			. Helpers::escapeHtml(Helpers::getClass($var))
			. '</span> <span class="tracy-dump-hash">#' . substr(md5(spl_object_hash($var)), 0, 4) . '</span>';

		if (empty($fields)) {
			return $out . "\n";

		} elseif (in_array($var, $options['parents'] ?? [], true)) {
			return $out . " { <i>RECURSION</i> }\n";

		} elseif (!$this->maxDepth || $level < $this->maxDepth || $var instanceof \Closure) {
			$collapsed = $level
				? count($fields) >= $this->collapseSub
				: (is_int($this->collapseTop) ? count($fields) >= $this->collapseTop : $this->collapseTop);

			$span = '<span class="tracy-toggle' . ($collapsed ? ' tracy-collapsed' : '') . '"';

			if ($collapsed && $options[self::LAZY] !== false) {
				return $span . " data-tracy-dump='"
					. json_encode($this->toJson($var, $options, $level), JSON_HEX_APOS | JSON_HEX_AMP)
					. "'>" . $out . "</span>\n";

			} else {
				$out = $span . '>' . $out . "</span>\n" . '<div' . ($collapsed ? ' class="tracy-collapsed"' : '') . '>';
				$options['parents'][] = $var;
				foreach ($fields as $k => &$v) {
					$vis = '';
					if (isset($k[0]) && $k[0] === "\x00") {
						$vis = ' <span class="tracy-dump-visibility">' . ($k[1] === '*' ? 'protected' : 'private') . '</span>';
						$k = substr($k, strrpos($k, "\x00") + 1);
					}
					$hide = is_string($k) && isset($this->keysToHide[strtolower($k)]);
					$out .= '<span class="tracy-dump-indent">   ' . str_repeat('|  ', $level) . '</span>'
						. '<span class="tracy-dump-key">' . Helpers::escapeHtml($this->encodeKey($k)) . "</span>$vis => "
						. (
							$hide
							? Helpers::escapeHtml(self::hideValue($v)) . "\n"
							: $this->dumpVar($v, $options, $level + 1)
						);
				}
				array_pop($options['parents']);

				return $out . '</div>';
			}


		} else {
			return $out . " { ... }\n";
		}
	}


	private function dumpResource(&$var, array $options, int $level): string
	{
		$type = is_resource($var) ? get_resource_type($var) : 'closed';
		$out = '<span class="tracy-dump-resource">' . Helpers::escapeHtml($type) . ' resource</span> '
			. '<span class="tracy-dump-hash">#' . (int) $var . '</span>';
		if (isset($this->resourceDumpers[$type])) {
			$out = "<span class=\"tracy-toggle tracy-collapsed\">$out</span>\n<div class=\"tracy-collapsed\">";
			foreach (($this->resourceDumpers[$type])($var) as $k => $v) {
				$out .= '<span class="tracy-dump-indent">   ' . str_repeat('|  ', $level) . '</span>'
					. '<span class="tracy-dump-key">' . Helpers::escapeHtml($k) . '</span> => ' . $this->dumpVar($v, $options, $level + 1);
			}
			return $out . '</div>';
		}
		return "$out\n";
	}


	/**
	 * @return mixed
	 */
	private function toJson(&$var, array $options = [], int $level = 0)
	{
		if (is_bool($var) || $var === null || is_int($var)) {
			return $var;

		} elseif (is_float($var)) {
			return is_finite($var)
				? (strpos($tmp = json_encode($var), '.') ? $var : ['number' => "$tmp.0"])
				: ['type' => (string) $var];

		} elseif (is_string($var)) {
			return $this->encodeString($var, $this->maxLength);

		} elseif (is_array($var)) {
			static $marker;
			if ($marker === null) {
				$marker = uniqid("\x00", true);
			}
			if (count($var) && (isset($var[$marker]) || $level >= $this->maxDepth)) {
				return ['stop' => [count($var) - isset($var[$marker]), isset($var[$marker])]];
			}
			$res = [];
			try {
				$var[$marker] = true;
				foreach ($var as $k => &$v) {
					if ($k === $marker) {
						continue;
					}
					$hide = is_string($k) && isset($this->keysToHide[strtolower($k)]);
					$res[] = [$this->encodeKey($k), $hide ? ['type' => self::hideValue($v)] : $this->toJson($v, $options, $level + 1)];
				}
			} finally {
				unset($var[$marker]);
			}
			return $res;

		} elseif (is_object($var)) {
			$hash = spl_object_hash($var);
			$obj = &$options[self::SNAPSHOT][$hash];
			if ($obj && $obj['level'] <= $level) {
				return ['object' => $obj['id']];
			}

			$obj = $obj ?: [
				'id' => count($options[self::SNAPSHOT]),
				'name' => Helpers::getClass($var),
				'hash' => substr(md5($hash), 0, 4),
				'level' => $level,
				'object' => $var,
			];
			if (empty($obj['editor']) && ($this->location & self::LOCATION_CLASS)) {
				$rc = $var instanceof \Closure
					? new \ReflectionFunction($var)
					: new \ReflectionClass($var);
				if ($editor = $rc->getFileName() ? Helpers::editorUri($rc->getFileName(), $rc->getStartLine()) : null) {
					$obj['editor'] = ['file' => $rc->getFileName(), 'line' => $rc->getStartLine(), 'url' => $editor];
				}
			}

			if ($level < $this->maxDepth || !$this->maxDepth) {
				$obj['level'] = $level;
				$obj['items'] = [];

				foreach ($this->exportObject($var) as $k => $v) {
					$vis = 0;
					if (isset($k[0]) && $k[0] === "\x00") {
						$vis = $k[1] === '*' ? 1 : 2;
						$k = substr($k, strrpos($k, "\x00") + 1);
					}
					$hide = is_string($k) && isset($this->keysToHide[strtolower($k)]);
					$obj['items'][] = [$this->encodeKey($k), $hide ? ['type' => self::hideValue($v)] : $this->toJson($v, $options, $level + 1), $vis];
				}
			}
			return ['object' => $obj['id']];

		} else {
			$obj = &$options[self::SNAPSHOT][(string) $var];
			if (!$obj) {
				$type = is_resource($var) ? get_resource_type($var) : 'closed';
				$obj = ['id' => count($options[self::SNAPSHOT]), 'name' => $type . ' resource', 'hash' => (int) $var, 'items' => []];
				if (isset($this->resourceDumpers[$type])) {
					foreach (($this->resourceDumpers[$type])($var) as $k => $v) {
						$obj['items'][] = [$k, $this->toJson($v, $options, $level + 1)];
					}
				}
			}
			return ['resource' => $obj['id']];
		}
	}


	public static function formatSnapshotAttribute(array &$snapshot): string
	{
		$res = [];
		foreach ($snapshot as $obj) {
			$id = $obj['id'];
			unset($obj['level'], $obj['object'], $obj['id']);
			$res[$id] = $obj;
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


	/**
	 * @param  object  $obj
	 */
	private function exportObject($obj): array
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
			$name = (string) $name;
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


	/** @internal */
	public static function hideValue($var): string
	{
		return self::HIDDEN_VALUE . ' (' . (is_object($var) ? Helpers::getClass($var) : gettype($var)) . ')';
	}


	/**
	 * Finds the location where dump was called. Returns [file, line, code]
	 */
	private static function findLocation(): ?array
	{
		foreach (debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS) as $item) {
			if (isset($item['class']) && $item['class'] === self::class) {
				$location = $item;
				continue;
			} elseif (isset($item['function'])) {
				try {
					$reflection = isset($item['class'])
						? new \ReflectionMethod($item['class'], $item['function'])
						: new \ReflectionFunction($item['function']);
					if (
						$reflection->isInternal()
						|| preg_match('#\s@tracySkipLocation\s#', (string) $reflection->getDocComment())
					) {
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
