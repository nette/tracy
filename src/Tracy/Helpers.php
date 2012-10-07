<?php

/**
 * This file is part of the Nette Framework (http://nette.org)
 *
 * Copyright (c) 2004 David Grudl (http://davidgrudl.com)
 *
 * For the full copyright and license information, please view
 * the file license.txt that was distributed with this source code.
 */

namespace Nette\Diagnostics;

use Nette;



/**
 * Rendering helpers for Debugger.
 *
 * @author     David Grudl
 */
final class Helpers
{
	/** @var int  how big array/object collapse? */
	public static $collapseLimit = 7;


	/**
	 * Returns link to editor.
	 * @return Nette\Utils\Html
	 */
	public static function editorLink($file, $line)
	{
		if (Debugger::$editor && is_file($file)) {
			$dir = dirname(strtr($file, '/', DIRECTORY_SEPARATOR));
			$base = isset($_SERVER['SCRIPT_FILENAME']) ? dirname(dirname(strtr($_SERVER['SCRIPT_FILENAME'], '/', DIRECTORY_SEPARATOR))) : dirname($dir);
			if (substr($dir, 0, strlen($base)) === $base) {
				$dir = '...' . substr($dir, strlen($base));
			}
			return Nette\Utils\Html::el('a')
				->href(strtr(Debugger::$editor, array('%file' => rawurlencode($file), '%line' => $line)))
				->title("$file:$line")
				->setHtml(htmlSpecialChars(rtrim($dir, DIRECTORY_SEPARATOR)) . DIRECTORY_SEPARATOR . '<b>' . htmlSpecialChars(basename($file)) . '</b>' . ($line ? ":$line" : ''));
		} else {
			return Nette\Utils\Html::el('span')->setText($file . ($line ? ":$line" : ''));
		}
	}



	/**
	 * @param  mixed  variable to dump
	 * @return string
	 */
	public static function textDump($var)
	{
		return htmlspecialchars_decode(strip_tags(self::htmlDump($var)), ENT_QUOTES);
	}



	/**
	 * Internal dump() implementation.
	 * @param  mixed  variable to dump
	 * @param  int    current recursion level
	 * @return string
	 */
	public static function htmlDump(&$var, $level = 0, $collapsed = FALSE)
	{
		static $tableUtf, $tableBin, $reBinary = '#[^\x09\x0A\x0D\x20-\x7E\xA0-\x{10FFFF}]#u';
		if ($tableUtf === NULL) {
			foreach (range("\x00", "\xFF") as $ch) {
				if (ord($ch) < 32 && strpos("\r\n\t", $ch) === FALSE) {
					$tableUtf[$ch] = $tableBin[$ch] = '\\x' . str_pad(dechex(ord($ch)), 2, '0', STR_PAD_LEFT);
				} elseif (ord($ch) < 127) {
					$tableUtf[$ch] = $tableBin[$ch] = $ch;
				} else {
					$tableUtf[$ch] = $ch; $tableBin[$ch] = '\\x' . dechex(ord($ch));
				}
			}
			$tableBin["\\"] = '\\\\';
			$tableBin["\r"] = '\\r';
			$tableBin["\n"] = '\\n';
			$tableBin["\t"] = '\\t';
			$tableUtf['\\x'] = $tableBin['\\x'] = '\\\\x';
		}
		$toggle = NULL;

		if (is_bool($var)) {
			return '<span class="nette-dump-bool">' . ($var ? 'TRUE' : 'FALSE') . "</span>\n";

		} elseif ($var === NULL) {
			return "<span class=\"nette-dump-null\">NULL</span>\n";

		} elseif (is_int($var)) {
			return "<span class=\"nette-dump-number\">$var</span>\n";

		} elseif (is_float($var)) {
			$var = var_export($var, TRUE);
			if (strpos($var, '.') === FALSE) {
				$var .= '.0';
			}
			return "<span class=\"nette-dump-number\">$var</span>\n";

		} elseif (is_string($var)) {
			if (Debugger::$maxLen && strlen($var) > Debugger::$maxLen) {
				$out = htmlSpecialChars(substr($var, 0, Debugger::$maxLen), ENT_NOQUOTES, 'ISO-8859-1') . ' ... ';
			} else {
				$out = htmlSpecialChars($var, ENT_NOQUOTES, 'ISO-8859-1');
			}
			$out = strtr($out, preg_match($reBinary, $out) || preg_last_error() ? $tableBin : $tableUtf);
			$len = strlen($var);
			return "<span class=\"nette-dump-string\">\"$out\"</span>" . ($len > 1 ? " ($len)" : "") . "\n";

		} elseif (is_array($var)) {
			$space = str_repeat($space1 = '   ', $level);
			$brackets = range(0, count($var) - 1) === array_keys($var) ? "[]" : "{}";

			static $marker;
			if ($marker === NULL) {
				$marker = uniqid("\x00", TRUE);
			}
			if (empty($var)) {
				$out = '';

			} elseif (isset($var[$marker])) {
				$brackets = $var[$marker];
				$out = " $brackets[0] *RECURSION* $brackets[1]";

			} elseif ($level < Debugger::$maxDepth || !Debugger::$maxDepth) {
				$collapsed |= count($var) >= self::$collapseLimit;
			    $toggle = '<span class="nette-toggle' . ($collapsed ? '-collapsed">' : '">');
		 		$out = '</span> <code' . ($collapsed ? ' class="nette-collapsed"' : '') . ">$brackets[0]\n";
				$var[$marker] = $brackets;
				foreach ($var as $k => &$v) {
					if ($k === $marker) {
						continue;
					}
					$k = strtr($k, preg_match($reBinary, $k) || preg_last_error() ? $tableBin : $tableUtf);
					$out .= $space . $space1
						. '<span class="nette-dump-key">' . htmlSpecialChars(preg_match('#^\w+$#', $k) ? $k : "\"$k\"") . '</span> => '
						. self::htmlDump($v, $level + 1);
				}
				unset($var[$marker]);
				$out .= "$space$brackets[1]</code>";

			} else {
				$out = " $brackets[0] ... $brackets[1]";
			}
			return $toggle . '<span class="nette-dump-array">array</span>(' . count($var) . ")$out\n";

		} elseif (is_object($var)) {
			if ($var instanceof \Closure) {
				$rc = new \ReflectionFunction($var);
				$arr = array();
				foreach ($rc->getParameters() as $param) {
					$arr[] = '$' . $param->getName();
				}
				$arr = array('file' => $rc->getFileName(), 'line' => $rc->getStartLine(), 'parameters' => implode(', ', $arr));
			} else {
				$arr = (array) $var;
			}
			$space = str_repeat($space1 = '   ', $level);

			static $list = array();
			if (empty($arr)) {
				$out = '';

			} elseif (in_array($var, $list, TRUE)) {
				$out = " { *RECURSION* }";

			} elseif ($level < Debugger::$maxDepth || !Debugger::$maxDepth || $var instanceof \Closure) {
				$collapsed |= count($arr) >= self::$collapseLimit;
			    $toggle = '<span class="nette-toggle' . ($collapsed ? '-collapsed">' : '">');
		 		$out = '</span> <code' . ($collapsed ? ' class="nette-collapsed"' : '') . ">{\n";
				$list[] = $var;
				foreach ($arr as $k => &$v) {
					$visibility = '';
					if ($k[0] === "\x00") {
						$visibility = ' <span class="nette-dump-visibility">' . ($k[1] === '*' ? 'protected' : 'private') . '</span>';
						$k = substr($k, strrpos($k, "\x00") + 1);
					}
					$k = strtr($k, preg_match($reBinary, $k) || preg_last_error() ? $tableBin : $tableUtf);
					$out .= $space . $space1
						. '<span class="nette-dump-key">' . htmlSpecialChars(preg_match('#^\w+$#', $k) ? $k : "\"$k\"") . "</span>$visibility => "
						. self::htmlDump($v, $level + 1);
				}
				array_pop($list);
				$out .= "$space}</code>";

			} else {
				$out = " { ... }";
			}
			return $toggle . '<span class="nette-dump-object">' . get_class($var) . "</span>(" . count($arr) . ")$out\n";

		} elseif (is_resource($var)) {
			$type = get_resource_type($var);
			$out = '';
			static $info = array('stream' => 'stream_get_meta_data', 'curl' => 'curl_getinfo');
			if (isset($info[$type])) {
				$space = str_repeat($space1 = '   ', $level);
			    $toggle = '<span class="nette-toggle-collapsed">';
		 		$out = "</span> <code class=\"nette-collapsed\">{\n";
				foreach (call_user_func($info[$type], $var) as $k => $v) {
					$out .= $space . $space1 . '<span class="nette-dump-key">' . htmlSpecialChars($k) . "</span> => " . self::htmlDump($v, $level + 1);
				}
				$out .= "$space}</code>";
			}
			return $toggle . '<span class="nette-dump-resource">' . htmlSpecialChars($type) . " resource</span>$out\n";

		} else {
			return "<span>unknown type</span>\n";
		}
	}



	/**
	 * Dumps variable.
	 * @param  string
	 * @return string
	 */
	public static function clickableDump($dump, $collapsed = FALSE)
	{
		return '<pre class="nette-dump">' . self::htmlDump($dump, 0, $collapsed) . '</pre>';
	}



	public static function findTrace(array $trace, $method, & $index = NULL)
	{
		$m = explode('::', $method);
		foreach ($trace as $i => $item) {
			if (isset($item['function']) && $item['function'] === end($m)
				&& isset($item['class']) === isset($m[1])
				&& (!isset($item['class']) || $item['class'] === $m[0] || $m[0] === '*' || is_subclass_of($item['class'], $m[0]))
			) {
				$index = $i;
				return $item;
			}
		}
	}

}
