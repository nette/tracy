<?php

/**
 * This file is part of the Tracy (http://tracy.nette.org)
 * Copyright (c) 2004 David Grudl (http://davidgrudl.com)
 */

namespace Tracy;

use Tracy;


/**
 * Dumps a variable.
 *
 * @author     David Grudl
 */
class Dumper
{
	const DEPTH = 'depth', // how many nested levels of array/object properties display (defaults to 4)
		TRUNCATE = 'truncate', // how truncate long strings? (defaults to 150)
		COLLAPSE = 'collapse', // collapse top array/object or how big are collapsed? (defaults to 14)
		COLLAPSE_COUNT = 'collapsecount', // how big array/object are collapsed? (defaults to 7)
		LOCATION = 'location', // show location string? (defaults to 0)
		OBJECT_EXPORTERS = 'exporters', // custom exporters for objects (defaults to Dumper::$objectexporters)
		LIVE = 'live'; // will be rendered using JavaScript

	const
		LOCATION_SOURCE = 1, // shows where dump was called
		LOCATION_LINK = 2, // appends clickable anchor
		LOCATION_CLASS = 4; // shows where class is defined

	/** @var array */
	public static $terminalColors = array(
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
	);

	/** @var array */
	public static $resources = array(
		'stream' => 'stream_get_meta_data',
		'stream-context' => 'stream_context_get_options',
		'curl' => 'curl_getinfo',
	);

	/** @var array */
	public static $objectExporters = array(
		'Closure' => 'Tracy\Dumper::exportClosure',
		'SplFileInfo' => 'Tracy\Dumper::exportSplFileInfo',
		'SplObjectStorage' => 'Tracy\Dumper::exportSplObjectStorage',
		'__PHP_Incomplete_Class' => 'Tracy\Dumper::exportPhpIncompleteClass',
	);

	/** @var array  */
	private static $liveStorage = array();


	/**
	 * Dumps variable to the output.
	 * @return mixed  variable
	 */
	public static function dump($var, array $options = NULL)
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
	 * @return string
	 */
	public static function toHtml($var, array $options = NULL)
	{
		$options = (array) $options + array(
			self::DEPTH => 4,
			self::TRUNCATE => 150,
			self::COLLAPSE => 14,
			self::COLLAPSE_COUNT => 7,
			self::OBJECT_EXPORTERS => NULL,
		);
		$loc = & $options[self::LOCATION];
		$loc = $loc === TRUE ? ~0 : (int) $loc;
		$options[self::OBJECT_EXPORTERS] = (array) $options[self::OBJECT_EXPORTERS] + self::$objectExporters;
		$live = !empty($options[self::LIVE]) && $var && (is_array($var) || is_object($var) || is_resource($var));
		list($file, $line, $code) = $loc ? self::findLocation() : NULL;
		$locAttrs = $file && $loc & self::LOCATION_SOURCE ? Helpers::formatHtml(
			' title="%in file % on line %" data-tracy-href="%"', "$code\n", $file, $line, Helpers::editorUri($file, $line)
		) : NULL;

		return '<pre class="tracy-dump' . ($live && $options[self::COLLAPSE] === TRUE ? ' tracy-collapsed' : '') . '"'
			. $locAttrs
			. ($live ? " data-tracy-dump='" . str_replace("'", '&#039;', json_encode(self::toJson($var, $options))) . "'>" : '>')
			. ($live ? '' : self::dumpVar($var, $options))
			. ($file && $loc & self::LOCATION_LINK ? '<small>in ' . Helpers::editorLink($file, $line) . '</small>' : '')
			. "</pre>\n";
	}


	/**
	 * Dumps variable to plain text.
	 * @return string
	 */
	public static function toText($var, array $options = NULL)
	{
		return htmlspecialchars_decode(strip_tags(self::toHtml($var, $options)), ENT_QUOTES);
	}


	/**
	 * Dumps variable to x-terminal.
	 * @return string
	 */
	public static function toTerminal($var, array $options = NULL)
	{
		return htmlspecialchars_decode(strip_tags(preg_replace_callback('#<span class="tracy-dump-(\w+)">|</span>#', function($m) {
			return "\033[" . (isset($m[1], Dumper::$terminalColors[$m[1]]) ? Dumper::$terminalColors[$m[1]] : '0') . 'm';
		}, self::toHtml($var, $options))), ENT_QUOTES);
	}


	/**
	 * Internal toHtml() dump implementation.
	 * @param  mixed  variable to dump
	 * @param  array  options
	 * @param  int    current recursion level
	 * @return string
	 */
	private static function dumpVar(& $var, array $options, $level = 0)
	{
		if (method_exists(__CLASS__, $m = 'dump' . gettype($var))) {
			return self::$m($var, $options, $level);
		} else {
			return "<span>unknown type</span>\n";
		}
	}


	private static function dumpNull()
	{
		return "<span class=\"tracy-dump-null\">NULL</span>\n";
	}


	private static function dumpBoolean(& $var)
	{
		return '<span class="tracy-dump-bool">' . ($var ? 'TRUE' : 'FALSE') . "</span>\n";
	}


	private static function dumpInteger(& $var)
	{
		return "<span class=\"tracy-dump-number\">$var</span>\n";
	}


	private static function dumpDouble(& $var)
	{
		$var = is_finite($var)
			? ($tmp = json_encode($var)) . (strpos($tmp, '.') === FALSE ? '.0' : '')
			: var_export($var, TRUE);
		return "<span class=\"tracy-dump-number\">$var</span>\n";
	}


	private static function dumpString(& $var, $options)
	{
		return '<span class="tracy-dump-string">"'
			. htmlspecialchars(self::encodeString($var, $options[self::TRUNCATE]), ENT_NOQUOTES, 'UTF-8')
			. '"</span>' . (strlen($var) > 1 ? ' (' . strlen($var) . ')' : '') . "\n";
	}


	private static function dumpArray(& $var, $options, $level)
	{
		static $marker;
		if ($marker === NULL) {
			$marker = uniqid("\x00", TRUE);
		}

		$out = '<span class="tracy-dump-array">array</span> (';

		if (empty($var)) {
			return $out . ")\n";

		} elseif (isset($var[$marker])) {
			return $out . (count($var) - 1) . ") [ <i>RECURSION</i> ]\n";

		} elseif (!$options[self::DEPTH] || $level < $options[self::DEPTH]) {
			$collapsed = $level ? count($var) >= $options[self::COLLAPSE_COUNT]
				: (is_int($options[self::COLLAPSE]) ? count($var) >= $options[self::COLLAPSE] : $options[self::COLLAPSE]);
			$out = '<span class="tracy-toggle' . ($collapsed ? ' tracy-collapsed' : '') . '">'
				. $out . count($var) . ")</span>\n<div" . ($collapsed ? ' class="tracy-collapsed"' : '') . '>';
			$var[$marker] = TRUE;
			foreach ($var as $k => & $v) {
				if ($k !== $marker) {
					$k = preg_match('#^\w{1,50}\z#', $k) ? $k : '"' . htmlspecialchars(self::encodeString($k, $options[self::TRUNCATE]), ENT_NOQUOTES, 'UTF-8') . '"';
					$out .= '<span class="tracy-dump-indent">   ' . str_repeat('|  ', $level) . '</span>'
						. '<span class="tracy-dump-key">' . $k . '</span> => '
						. self::dumpVar($v, $options, $level + 1);
				}
			}
			unset($var[$marker]);
			return $out . '</div>';

		} else {
			return $out . count($var) . ") [ ... ]\n";
		}
	}


	private static function dumpObject(& $var, $options, $level)
	{
		$fields = self::exportObject($var, $options[self::OBJECT_EXPORTERS]);
		$editor = NULL;
		if ($options[self::LOCATION] & self::LOCATION_CLASS) {
			$rc = $var instanceof \Closure ? new \ReflectionFunction($var) : new \ReflectionClass($var);
			$editor = Helpers::editorUri($rc->getFileName(), $rc->getStartLine());
		}
		$out = '<span class="tracy-dump-object"'
			. ($editor ? Helpers::formatHtml(
				' title="Declared in file % on line %" data-tracy-href="%"', $rc->getFileName(), $rc->getStartLine(), $editor
			) : '')
			. '>' . get_class($var) . '</span> <span class="tracy-dump-hash">#' . substr(md5(spl_object_hash($var)), 0, 4) . '</span>';

		static $list = array();

		if (empty($fields)) {
			return $out . "\n";

		} elseif (in_array($var, $list, TRUE)) {
			return $out . " { <i>RECURSION</i> }\n";

		} elseif (!$options[self::DEPTH] || $level < $options[self::DEPTH] || $var instanceof \Closure) {
			$collapsed = $level ? count($fields) >= $options[self::COLLAPSE_COUNT]
				: (is_int($options[self::COLLAPSE]) ? count($fields) >= $options[self::COLLAPSE] : $options[self::COLLAPSE]);
			$out = '<span class="tracy-toggle' . ($collapsed ? ' tracy-collapsed' : '') . '">'
				. $out . "</span>\n<div" . ($collapsed ? ' class="tracy-collapsed"' : '') . '>';
			$list[] = $var;
			foreach ($fields as $k => & $v) {
				$vis = '';
				if ($k[0] === "\x00") {
					$vis = ' <span class="tracy-dump-visibility">' . ($k[1] === '*' ? 'protected' : 'private') . '</span>';
					$k = substr($k, strrpos($k, "\x00") + 1);
				}
				$k = preg_match('#^\w{1,50}\z#', $k) ? $k : '"' . htmlspecialchars(self::encodeString($k, $options[self::TRUNCATE]), ENT_NOQUOTES, 'UTF-8') . '"';
				$out .= '<span class="tracy-dump-indent">   ' . str_repeat('|  ', $level) . '</span>'
					. '<span class="tracy-dump-key">' . $k . "</span>$vis => "
					. self::dumpVar($v, $options, $level + 1);
			}
			array_pop($list);
			return $out . '</div>';

		} else {
			return $out . " { ... }\n";
		}
	}


	private static function dumpResource(& $var, $options, $level)
	{
		$type = get_resource_type($var);
		$out = '<span class="tracy-dump-resource">' . htmlSpecialChars($type, ENT_IGNORE, 'UTF-8') . ' resource</span> '
			. '<span class="tracy-dump-hash">#' . intval($var) . '</span>';
		if (isset(self::$resources[$type])) {
			$out = "<span class=\"tracy-toggle tracy-collapsed\">$out</span>\n<div class=\"tracy-collapsed\">";
			foreach (call_user_func(self::$resources[$type], $var) as $k => $v) {
				$out .= '<span class="tracy-dump-indent">   ' . str_repeat('|  ', $level) . '</span>'
					. '<span class="tracy-dump-key">' . htmlSpecialChars($k, ENT_IGNORE, 'UTF-8') . "</span> => " . self::dumpVar($v, $options, $level + 1);
			}
			return $out . '</div>';
		}
		return "$out\n";
	}


	/**
	 * @return mixed
	 */
	private static function toJson(& $var, $options, $level = 0)
	{
		if (is_bool($var) || is_null($var) || is_int($var) || is_float($var)) {
			return is_finite($var) ? $var : array('type' => (string) $var);

		} elseif (is_string($var)) {
			return self::encodeString($var, $options[self::TRUNCATE]);

		} elseif (is_array($var)) {
			static $marker;
			if ($marker === NULL) {
				$marker = uniqid("\x00", TRUE);
			}
			if (isset($var[$marker]) || $level >= $options[self::DEPTH]) {
				return array(NULL);
			}
			$res = array();
			$var[$marker] = TRUE;
			foreach ($var as $k => & $v) {
				if ($k !== $marker) {
					$k = preg_match('#^\w{1,50}\z#', $k) ? $k : '"' . self::encodeString($k, $options[self::TRUNCATE]) . '"';
					$res[] = array($k, self::toJson($v, $options, $level + 1));
				}
			}
			unset($var[$marker]);
			return $res;

		} elseif (is_object($var)) {
			$obj = & self::$liveStorage[spl_object_hash($var)];
			if ($obj && $obj['level'] <= $level) {
				return array('object' => $obj['id']);
			}

			if ($options[self::LOCATION] & self::LOCATION_CLASS) {
				$rc = $var instanceof \Closure ? new \ReflectionFunction($var) : new \ReflectionClass($var);
				$editor = Helpers::editorUri($rc->getFileName(), $rc->getStartLine());
			}
			static $counter = 1;
			$obj = $obj ?: array(
				'id' => '0' . $counter++, // differentiate from resources
				'name' => get_class($var),
				'editor' => empty($editor) ? NULL : array('file' => $rc->getFileName(), 'line' => $rc->getStartLine(), 'url' => $editor),
				'level' => $level,
				'object' => $var,
			);

			if ($level < $options[self::DEPTH] || !$options[self::DEPTH]) {
				$obj['level'] = $level;
				$obj['items'] = array();

				foreach (self::exportObject($var, $options[self::OBJECT_EXPORTERS]) as $k => $v) {
					$vis = 0;
					if ($k[0] === "\x00") {
						$vis = $k[1] === '*' ? 1 : 2;
						$k = substr($k, strrpos($k, "\x00") + 1);
					}
					$k = preg_match('#^\w{1,50}\z#', $k) ? $k : '"' . self::encodeString($k, $options[self::TRUNCATE]) . '"';
					$obj['items'][] = array($k, self::toJson($v, $options, $level + 1), $vis);
				}
			}
			return array('object' => $obj['id']);

		} elseif (is_resource($var)) {
			$obj = & self::$liveStorage[(string) $var];
			if (!$obj) {
				$type = get_resource_type($var);
				$obj = array('id' => (int) $var, 'name' => $type . ' resource');
				if (isset(self::$resources[$type])) {
					foreach (call_user_func(self::$resources[$type], $var) as $k => $v) {
						$obj['items'][] = array($k, self::toJson($v, $options, $level + 1));
					}
				}
			}
			return array('resource' => $obj['id']);

		} else {
			return 'unknown type';
		}
	}


	/** @return array  */
	public static function fetchLiveData()
	{
		$res = array();
		foreach (self::$liveStorage as $obj) {
			$id = $obj['id'];
			unset($obj['level'], $obj['object'], $obj['id']);
			$res[$id] = $obj;
		}
		self::$liveStorage = array();
		return $res;
	}


	/**
	 * @internal
	 * @return string UTF-8
	 */
	public static function encodeString($s, $maxLength = NULL)
	{
		static $table;
		if ($table === NULL) {
			foreach (array_merge(range("\x00", "\x1F"), range("\x7F", "\xFF")) as $ch) {
				$table[$ch] = '\x' . str_pad(dechex(ord($ch)), 2, '0', STR_PAD_LEFT);
			}
			$table["\\"] = '\\\\';
			$table["\r"] = '\r';
			$table["\n"] = '\n';
			$table["\t"] = '\t';
		}

		if (preg_match('#[^\x09\x0A\x0D\x20-\x7E\xA0-\x{10FFFF}]#u', $s) || preg_last_error()) {
			if ($maxLength && strlen($s) > $maxLength) {
				$s = substr($s, 0, $maxLength) . ' ... ';
			}
			$s = strtr($s, $table);
		} elseif ($maxLength && strlen(utf8_decode($s)) > $maxLength) {
			$s = iconv_substr($s, 0, $maxLength, 'UTF-8') . ' ... ';
		}

		return $s;
	}


	/**
	 * @return array
	 */
	private static function exportObject($obj, array $exporters)
	{
		foreach ($exporters as $type => $dumper) {
			if ($obj instanceof $type) {
				return call_user_func($dumper, $obj);
			}
		}
		return (array) $obj;
	}


	/**
	 * @return array
	 */
	private static function exportClosure(\Closure $obj)
	{
		$rc = new \ReflectionFunction($obj);
		$res = array();
		foreach ($rc->getParameters() as $param) {
			$res[] = '$' . $param->getName();
		}
		return array(
			'file' => $rc->getFileName(),
			'line' => $rc->getStartLine(),
			'variables' => $rc->getStaticVariables(),
			'parameters' => implode(', ', $res),
		);
	}


	/**
	 * @return array
	 */
	private static function exportSplFileInfo(\SplFileInfo $obj)
	{
		return array('path' => $obj->getPathname());
	}


	/**
	 * @return array
	 */
	private static function exportSplObjectStorage(\SplObjectStorage $obj)
	{
		$res = array();
		foreach (clone $obj as $item) {
			$res[] = array('object' => $item, 'data' => $obj[$item]);
		}
		return $res;
	}


	/**
	 * @return array
	 */
	private static function exportPhpIncompleteClass(\__PHP_Incomplete_Class $obj)
	{
		$info = array('className' => NULL, 'private' => array(), 'protected' => array(), 'public' => array());
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
	 * Finds the location where dump was called.
	 * @return array [file, line, code]
	 */
	private static function findLocation()
	{
		foreach (debug_backtrace(PHP_VERSION_ID >= 50306 ? DEBUG_BACKTRACE_IGNORE_ARGS : FALSE) as $item) {
			if (isset($item['class']) && $item['class'] === __CLASS__) {
				$location = $item;
				continue;
			} elseif (isset($item['function'])) {
				try {
					$reflection = isset($item['class'])
						? new \ReflectionMethod($item['class'], $item['function'])
						: new \ReflectionFunction($item['function']);
					if ($reflection->isInternal() || preg_match('#\s@tracySkipLocation\s#', $reflection->getDocComment())) {
						$location = $item;
						continue;
					}
				} catch (\ReflectionException $e) {}
			}
			break;
		}

		if (isset($location['file'], $location['line']) && is_file($location['file'])) {
			$lines = file($location['file']);
			$line = $lines[$location['line'] - 1];
			return array(
				$location['file'],
				$location['line'],
				trim(preg_match('#\w*dump(er::\w+)?\(.*\)#i', $line, $m) ? $m[0] : $line)
			);
		}
	}


	/**
	 * @return bool
	 */
	private static function detectColors()
	{
		return self::$terminalColors &&
			(getenv('ConEmuANSI') === 'ON'
			|| getenv('ANSICON') !== FALSE
			|| (defined('STDOUT') && function_exists('posix_isatty') && posix_isatty(STDOUT)));
	}

}
