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
		LIVE = 'live', // will be rendered using JavaScript
		SNAPSHOT = 'snapshot', // array for shared snapshot, enables LIVE
		DEBUGINFO = 'debuginfo', // use magic method __debugInfo if exists (defaults to false)
		KEYS_TO_HIDE = 'keystohide'; // sensitive keys not displayed (defaults to [])

	public const
		LOCATION_SOURCE = 0b0001, // shows where dump was called
		LOCATION_LINK = 0b0010, // appends clickable anchor
		LOCATION_CLASS = 0b0100; // shows where class is defined

	public const
		HIDDEN_VALUE = '*****';

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
		'Closure' => 'Tracy\Dumper::exportClosure',
		'SplFileInfo' => 'Tracy\Dumper::exportSplFileInfo',
		'SplObjectStorage' => 'Tracy\Dumper::exportSplObjectStorage',
		'__PHP_Incomplete_Class' => 'Tracy\Dumper::exportPhpIncompleteClass',
	];


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
		$options += [
			self::DEPTH => 4,
			self::TRUNCATE => 150,
			self::COLLAPSE => 14,
			self::COLLAPSE_COUNT => 7,
			self::OBJECT_EXPORTERS => null,
			self::DEBUGINFO => false,
			self::KEYS_TO_HIDE => [],
			self::LIVE => null,
		];

		$options[self::KEYS_TO_HIDE] = array_flip(array_map('strtolower', $options[self::KEYS_TO_HIDE]));
		$options[self::OBJECT_EXPORTERS] = (array) $options[self::OBJECT_EXPORTERS] + self::$objectExporters;
		uksort($options[self::OBJECT_EXPORTERS], function ($a, $b): int {
			return $b === '' || (class_exists($a, false) && is_subclass_of($a, $b)) ? -1 : 1;
		});

		$loc = &$options[self::LOCATION];
		$loc = $loc === true ? ~0 : (int) $loc;
		[$file, $line, $code] = $loc ? self::findLocation() : null;
		$locAttrs = $file && $loc & self::LOCATION_SOURCE ? Helpers::formatHtml(
			' title="%in file % on line %" data-tracy-href="%"', "$code\n", $file, $line, Helpers::editorUri($file, $line)
		) : null;

		$snapshot = &$options[self::SNAPSHOT]; // must be reference
		if ($sharedSnapshot = is_array($snapshot)) {
			$options[self::LIVE] = true;
		}
		$live = null;
		if ($options[self::LIVE] && (is_array($var) || is_object($var) || is_resource($var)) && $var) {
			$live = self::toJson($var, $options);
		}

		return '<pre class="tracy-dump' . ($live && $options[self::COLLAPSE] === true ? ' tracy-collapsed' : '') . '"'
			. $locAttrs
			. ($snapshot && !$sharedSnapshot ? ' ' . self::formatSnapshotAttribute($snapshot) : '')
			. ($live ? " data-tracy-dump='" . json_encode($live, JSON_HEX_APOS | JSON_HEX_AMP) . "'>" : '>')
			. ($live ? '' : self::dumpVar($var, $options))
			. ($file && $loc & self::LOCATION_LINK ? '<small>in ' . Helpers::editorLink($file, $line) . '</small>' : '')
			. "</pre>\n";
	}


	/**
	 * Dumps variable to plain text.
	 */
	public static function toText($var, array $options = []): string
	{
		$s = self::toHtml($var, $options);
		return htmlspecialchars_decode(strip_tags($s), ENT_QUOTES);
	}


	/**
	 * Dumps variable to x-terminal.
	 */
	public static function toTerminal($var, array $options = []): string
	{
		$s = self::toHtml($var, $options);
		$s = preg_replace_callback('#<span class="tracy-dump-(\w+)">|</span>#', function ($m): string {
			return "\033[" . (isset($m[1], self::$terminalColors[$m[1]]) ? self::$terminalColors[$m[1]] : '0') . 'm';
		}, $s);
		$s = htmlspecialchars_decode(strip_tags($s), ENT_QUOTES);
		return $s;
	}


	/**
	 * Internal toHtml() dump implementation.
	 * @param  mixed  $var
	 */
	private static function dumpVar(&$var, array $options, int $level = 0): string
	{
		if (method_exists(__CLASS__, $m = 'dump' . gettype($var))) {
			return self::$m($var, $options, $level);
		} else {
			return "<span>unknown type</span>\n";
		}
	}


	private static function dumpNull(): string
	{
		return "<span class=\"tracy-dump-null\">null</span>\n";
	}


	private static function dumpBoolean(&$var): string
	{
		return '<span class="tracy-dump-bool">' . ($var ? 'true' : 'false') . "</span>\n";
	}


	private static function dumpInteger(&$var): string
	{
		return "<span class=\"tracy-dump-number\">$var</span>\n";
	}


	private static function dumpDouble(&$var): string
	{
		$var = is_finite($var)
			? ($tmp = json_encode($var)) . (strpos($tmp, '.') === false ? '.0' : '')
			: var_export($var, true);
		return "<span class=\"tracy-dump-number\">$var</span>\n";
	}


	private static function dumpString(&$var, array $options): string
	{
		return '<span class="tracy-dump-string">"'
			. Helpers::escapeHtml(self::encodeString($var, $options[self::TRUNCATE]))
			. '"</span>' . (strlen($var) > 1 ? ' (' . strlen($var) . ')' : '') . "\n";
	}


	private static function dumpArray(&$var, array $options, int $level): string
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

		} elseif (!$options[self::DEPTH] || $level < $options[self::DEPTH]) {
			$collapsed = $level
				? count($var) >= $options[self::COLLAPSE_COUNT]
				: (is_int($options[self::COLLAPSE]) ? count($var) >= $options[self::COLLAPSE] : $options[self::COLLAPSE]);

			$out = '<span class="tracy-toggle' . ($collapsed ? ' tracy-collapsed' : '') . '">'
				. $out . count($var) . ")</span>\n<div" . ($collapsed ? ' class="tracy-collapsed"' : '') . '>';
			$var[$marker] = true;
			foreach ($var as $k => &$v) {
				if ($k !== $marker) {
					$hide = is_string($k) && isset($options[self::KEYS_TO_HIDE][strtolower($k)]) ? self::HIDDEN_VALUE : null;
					$out .= '<span class="tracy-dump-indent">   ' . str_repeat('|  ', $level) . '</span>'
						. '<span class="tracy-dump-key">' . Helpers::escapeHtml(self::encodeKey($k, $options)) . '</span> => '
						. ($hide ? self::dumpString($hide, $options) : self::dumpVar($v, $options, $level + 1));
				}
			}
			unset($var[$marker]);
			return $out . '</div>';

		} else {
			return $out . count($var) . ") [ ... ]\n";
		}
	}


	private static function dumpObject(&$var, array $options, int $level): string
	{
		$fields = self::exportObject($var, $options[self::OBJECT_EXPORTERS], $options[self::DEBUGINFO]);

		$editorAttributes = '';
		if ($options[self::LOCATION] & self::LOCATION_CLASS) {
			$rc = $var instanceof \Closure ? new \ReflectionFunction($var) : new \ReflectionClass($var);
			$editor = $rc->getFileName() ? Helpers::editorUri($rc->getFileName(), $rc->getStartLine()) : null;
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

		static $list = [];

		if (empty($fields)) {
			return $out . "\n";

		} elseif (in_array($var, $list, true)) {
			return $out . " { <i>RECURSION</i> }\n";

		} elseif (!$options[self::DEPTH] || $level < $options[self::DEPTH] || $var instanceof \Closure) {
			$collapsed = $level
				? count($fields) >= $options[self::COLLAPSE_COUNT]
				: (is_int($options[self::COLLAPSE]) ? count($fields) >= $options[self::COLLAPSE] : $options[self::COLLAPSE]);

			$out = '<span class="tracy-toggle' . ($collapsed ? ' tracy-collapsed' : '') . '">'
				. $out . "</span>\n<div" . ($collapsed ? ' class="tracy-collapsed"' : '') . '>';
			$list[] = $var;
			foreach ($fields as $k => &$v) {
				$vis = '';
				if (isset($k[0]) && $k[0] === "\x00") {
					$vis = ' <span class="tracy-dump-visibility">' . ($k[1] === '*' ? 'protected' : 'private') . '</span>';
					$k = substr($k, strrpos($k, "\x00") + 1);
				}
				$hide = is_string($k) && isset($options[self::KEYS_TO_HIDE][strtolower($k)]) ? self::HIDDEN_VALUE : null;
				$out .= '<span class="tracy-dump-indent">   ' . str_repeat('|  ', $level) . '</span>'
					. '<span class="tracy-dump-key">' . Helpers::escapeHtml(self::encodeKey($k, $options)) . "</span>$vis => "
					. ($hide ? self::dumpString($hide, $options) : self::dumpVar($v, $options, $level + 1));
			}
			array_pop($list);
			return $out . '</div>';

		} else {
			return $out . " { ... }\n";
		}
	}


	private static function dumpResource(&$var, array $options, int $level): string
	{
		$type = get_resource_type($var);
		$out = '<span class="tracy-dump-resource">' . Helpers::escapeHtml($type) . ' resource</span> '
			. '<span class="tracy-dump-hash">#' . (int) $var . '</span>';
		if (isset(self::$resources[$type])) {
			$out = "<span class=\"tracy-toggle tracy-collapsed\">$out</span>\n<div class=\"tracy-collapsed\">";
			foreach ((self::$resources[$type])($var) as $k => $v) {
				$out .= '<span class="tracy-dump-indent">   ' . str_repeat('|  ', $level) . '</span>'
					. '<span class="tracy-dump-key">' . Helpers::escapeHtml($k) . '</span> => ' . self::dumpVar($v, $options, $level + 1);
			}
			return $out . '</div>';
		}
		return "$out\n";
	}


	/**
	 * @return mixed
	 */
	private static function toJson(&$var, array $options, int $level = 0)
	{
		if (is_bool($var) || $var === null || is_int($var)) {
			return $var;

		} elseif (is_float($var)) {
			return is_finite($var)
				? (strpos($tmp = json_encode($var), '.') ? $var : ['number' => "$tmp.0"])
				: ['type' => (string) $var];

		} elseif (is_string($var)) {
			return self::encodeString($var, $options[self::TRUNCATE]);

		} elseif (is_array($var)) {
			static $marker;
			if ($marker === null) {
				$marker = uniqid("\x00", true);
			}
			if (isset($var[$marker]) || $level >= $options[self::DEPTH]) {
				return [null];
			}
			$res = [];
			$var[$marker] = true;
			foreach ($var as $k => &$v) {
				if ($k !== $marker) {
					$hide = is_string($k) && isset($options[self::KEYS_TO_HIDE][strtolower($k)]);
					$res[] = [self::encodeKey($k, $options), $hide ? self::HIDDEN_VALUE : self::toJson($v, $options, $level + 1)];
				}
			}
			unset($var[$marker]);
			return $res;

		} elseif (is_object($var)) {
			$obj = &$options[self::SNAPSHOT][spl_object_hash($var)];
			if ($obj && $obj['level'] <= $level) {
				return ['object' => $obj['id']];
			}

			$editorInfo = null;
			if ($options[self::LOCATION] & self::LOCATION_CLASS) {
				$rc = $var instanceof \Closure ? new \ReflectionFunction($var) : new \ReflectionClass($var);
				$editor = $rc->getFileName() ? Helpers::editorUri($rc->getFileName(), $rc->getStartLine()) : null;
				$editorInfo = $editor ? ['file' => $rc->getFileName(), 'line' => $rc->getStartLine(), 'url' => $editor] : null;
			}
			static $counter = 1;
			$obj = $obj ?: [
				'id' => '0' . $counter++, // differentiate from resources
				'name' => Helpers::getClass($var),
				'editor' => $editorInfo,
				'level' => $level,
				'object' => $var,
			];

			if ($level < $options[self::DEPTH] || !$options[self::DEPTH]) {
				$obj['level'] = $level;
				$obj['items'] = [];

				foreach (self::exportObject($var, $options[self::OBJECT_EXPORTERS], $options[self::DEBUGINFO]) as $k => $v) {
					$vis = 0;
					if (isset($k[0]) && $k[0] === "\x00") {
						$vis = $k[1] === '*' ? 1 : 2;
						$k = substr($k, strrpos($k, "\x00") + 1);
					}
					$hide = is_string($k) && isset($options[self::KEYS_TO_HIDE][strtolower($k)]);
					$obj['items'][] = [self::encodeKey($k, $options), $hide ? self::HIDDEN_VALUE : self::toJson($v, $options, $level + 1), $vis];
				}
			}
			return ['object' => $obj['id']];

		} elseif (is_resource($var)) {
			$obj = &$options[self::SNAPSHOT][(string) $var];
			if (!$obj) {
				$type = get_resource_type($var);
				$obj = ['id' => (int) $var, 'name' => $type . ' resource'];
				if (isset(self::$resources[$type])) {
					foreach ((self::$resources[$type])($var) as $k => $v) {
						$obj['items'][] = [$k, self::toJson($v, $options, $level + 1)];
					}
				}
			}
			return ['resource' => $obj['id']];

		} else {
			return ['type' => 'unknown type'];
		}
	}


	public static function formatSnapshotAttribute(array $snapshot): string
	{
		$res = [];
		foreach ($snapshot as $obj) {
			$id = $obj['id'];
			unset($obj['level'], $obj['object'], $obj['id']);
			$res[$id] = $obj;
		}
		return "data-tracy-snapshot='" . json_encode($res, JSON_HEX_APOS | JSON_HEX_AMP) . "'";
	}


	/**
	 * @internal
	 */
	public static function encodeString(string $s, int $maxLength = null): string
	{
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

		if ($maxLength && strlen($s) > $maxLength) { // shortens to $maxLength in UTF-8 or longer
			if (function_exists('mb_substr')) {
				$s = mb_substr($tmp = $s, 0, $maxLength, 'UTF-8');
				$shortened = $s !== $tmp;
			} else {
				$i = $len = 0;
				$maxI = $maxLength * 4; // max UTF-8 length
				do {
					if (($s[$i] < "\x80" || $s[$i] >= "\xC0") && (++$len > $maxLength) || $i >= $maxI) {
						$s = substr($s, 0, $i);
						$shortened = true;
						break;
					}
				} while (isset($s[++$i]));
			}
		}

		if (preg_match('#[^\x09\x0A\x0D\x20-\x7E\xA0-\x{10FFFF}]#u', $s) || preg_last_error()) { // is binary?
			if ($maxLength && strlen($s) > $maxLength) {
				$s = substr($s, 0, $maxLength);
				$shortened = true;
			}
			$s = strtr($s, $table);
		}

		return $s . (empty($shortened) ? '' : ' ... ');
	}


	/**
	 * @param  int|string  $k
	 * @return int|string
	 */
	private static function encodeKey($key, array $options)
	{
		return is_int($key) || preg_match('#^[!\#$%&()*+,./0-9:;<=>?@A-Z[\]^_`a-z{|}~-]{1,50}\z#', $key)
			? $key
			: '"' . self::encodeString($key, $options[self::TRUNCATE]) . '"';
	}


	/**
	 * @param  object  $obj
	 */
	private static function exportObject($obj, array $exporters, bool $useDebugInfo): array
	{
		foreach ($exporters as $type => $dumper) {
			if (!$type || $obj instanceof $type) {
				return $dumper($obj);
			}
		}

		if ($useDebugInfo && method_exists($obj, '__debugInfo')) {
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
			} elseif (preg_match('#^\x0\*\x0(.+)\z#', $name, $m)) {
				$info['protected'][$m[1]] = $value;
			} elseif (preg_match('#^\x0(.+)\x0(.+)\z#', $name, $m)) {
				$info['private'][$m[1] . '::$' . $m[2]] = $value;
			} else {
				$info['public'][$name] = $value;
			}
		}
		return $info;
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
