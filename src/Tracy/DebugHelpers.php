<?php

/**
 * This file is part of the Nette Framework.
 *
 * Copyright (c) 2004, 2011 David Grudl (http://davidgrudl.com)
 *
 * This source file is subject to the "Nette license", and/or
 * GPL license. For more information please see http://nette.org
 */

namespace Nette;

use Nette,
	Nette\Environment;



/**
 * Rendering helpers for Nette\Debug.
 *
 * @author     David Grudl
 * @internal
 */
final class DebugHelpers
{

	/**
	 * Renders blue screen.
	 * @param  \Exception
	 * @return void
	 */
	public static function renderBlueScreen(\Exception $exception)
	{
		if (class_exists('Nette\Environment', FALSE)) {
			$application = Environment::getContext()->hasService('Nette\\Application\\Application', TRUE) ? Environment::getContext()->getService('Nette\\Application\\Application') : NULL;
		}

		require __DIR__ . '/templates/bluescreen.phtml';
	}



	/**
	 * Renders debug bar.
	 * @param  array
	 * @return void
	 */
	public static function renderDebugBar($panels)
	{
		foreach ($panels as $key => $panel) {
			$panels[$key] = array(
				'id' => preg_replace('#[^a-z0-9]+#i', '-', $panel->getId()),
				'tab' => $tab = (string) $panel->getTab(),
				'panel' => $tab ? (string) $panel->getPanel() : NULL,
			);
		}
		require __DIR__ . '/templates/bar.phtml';
	}



	/**
	 * Renders default panel.
	 * @param  string
	 * @param  mixed
	 * @return void
	 */
	public static function renderTab($id, $data)
	{
		switch ($id) {
		case 'time':
			require __DIR__ . '/templates/bar.time.tab.phtml';
			return;
		case 'memory':
			require __DIR__ . '/templates/bar.memory.tab.phtml';
			return;
		case 'dumps':
			if (!$data) return;
			require __DIR__ . '/templates/bar.dumps.tab.phtml';
			return;
		case 'errors':
			if (!$data) return;
			require __DIR__ . '/templates/bar.errors.tab.phtml';
		}
	}



	/**
	 * Renders default panel.
	 * @param  string
	 * @param  mixed
	 * @return void
	 */
	public static function renderPanel($id, $data)
	{
		switch ($id) {
		case 'dumps':
			require __DIR__ . '/templates/bar.dumps.panel.phtml';
			return;
		case 'errors':
			require __DIR__ . '/templates/bar.errors.panel.phtml';
		}
	}



	/**
	 * Returns syntax highlighted source code.
	 * @param  string
	 * @param  int
	 * @param  int
	 * @return string
	 */
	public static function highlightFile($file, $line, $count = 15)
	{
		if (function_exists('ini_set')) {
			ini_set('highlight.comment', '#999; font-style: italic');
			ini_set('highlight.default', '#000');
			ini_set('highlight.html', '#06B');
			ini_set('highlight.keyword', '#D24; font-weight: bold');
			ini_set('highlight.string', '#080');
		}

		$start = max(1, $line - floor($count / 2));

		$source = @file_get_contents($file);
		if (!$source) return;
		$source = explode("\n", highlight_string($source, TRUE));
		$spans = 1;
		$out = $source[0]; // <code><span color=highlight.html>
		$source = explode('<br />', $source[1]);
		array_unshift($source, NULL);

		$i = $start; // find last highlighted block
		while (--$i >= 1) {
			if (preg_match('#.*(</?span[^>]*>)#', $source[$i], $m)) {
				if ($m[1] !== '</span>') { $spans++; $out .= $m[1]; }
				break;
			}
		}

		$source = array_slice($source, $start, $count, TRUE);
		end($source);
		$numWidth = strlen((string) key($source));

		foreach ($source as $n => $s) {
			$spans += substr_count($s, '<span') - substr_count($s, '</span');
			$s = str_replace(array("\r", "\n"), array('', ''), $s);
			preg_match_all('#<[^>]+>#', $s, $tags);
			if ($n === $line) {
				$out .= sprintf(
					"<span class='highlight'>%{$numWidth}s:    %s\n</span>%s",
					$n,
					strip_tags($s),
					implode('', $tags[0])
				);
			} else {
				$out .= sprintf("<span class='line'>%{$numWidth}s:</span>    %s\n", $n, $s);
			}
		}
		return $out . str_repeat('</span>', $spans) . '</code>';
	}



	/**
	 * Returns link to editor.
	 * @return string
	 */
	public static function editorLink($file, $line)
	{
		return strtr(Debug::$editor, array('%file' => rawurlencode($file), '%line' => $line));
	}



	/********************* dumps ****************d*g**/



	/**
	 * Internal dump() implementation.
	 * @param  mixed  variable to dump
	 * @param  int    current recursion level
	 * @return string
	 */
	public static function htmlDump(&$var, $level = 0)
	{
		static $tableUtf, $tableBin, $reBinary = '#[^\x09\x0A\x0D\x20-\x7E\xA0-\x{10FFFF}]#u';
		if ($tableUtf === NULL) {
			foreach (range("\x00", "\xFF") as $ch) {
				if (ord($ch) < 32 && strpos("\r\n\t", $ch) === FALSE) $tableUtf[$ch] = $tableBin[$ch] = '\\x' . str_pad(dechex(ord($ch)), 2, '0', STR_PAD_LEFT);
				elseif (ord($ch) < 127) $tableUtf[$ch] = $tableBin[$ch] = $ch;
				else { $tableUtf[$ch] = $ch; $tableBin[$ch] = '\\x' . dechex(ord($ch)); }
			}
			$tableBin["\\"] = '\\\\';
			$tableBin["\r"] = '\\r';
			$tableBin["\n"] = '\\n';
			$tableBin["\t"] = '\\t';
			$tableUtf['\\x'] = $tableBin['\\x'] = '\\\\x';
		}

		if (is_bool($var)) {
			return ($var ? 'TRUE' : 'FALSE') . "\n";

		} elseif ($var === NULL) {
			return "NULL\n";

		} elseif (is_int($var)) {
			return "$var\n";

		} elseif (is_float($var)) {
			$var = (string) $var;
			if (strpos($var, '.') === FALSE) $var .= '.0';
			return "$var\n";

		} elseif (is_string($var)) {
			if (Debug::$maxLen && strlen($var) > Debug::$maxLen) {
				$s = htmlSpecialChars(substr($var, 0, Debug::$maxLen), ENT_NOQUOTES) . ' ... ';
			} else {
				$s = htmlSpecialChars($var, ENT_NOQUOTES);
			}
			$s = strtr($s, preg_match($reBinary, $s) || preg_last_error() ? $tableBin : $tableUtf);
			$len = strlen($var);
			return "\"$s\"" . ($len > 1 ? " ($len)" : "") . "\n";

		} elseif (is_array($var)) {
			$s = "<span>array</span>(" . count($var) . ") ";
			$space = str_repeat($space1 = '   ', $level);
			$brackets = range(0, count($var) - 1) === array_keys($var) ? "[]" : "{}";

			static $marker;
			if ($marker === NULL) $marker = uniqid("\x00", TRUE);
			if (empty($var)) {

			} elseif (isset($var[$marker])) {
				$brackets = $var[$marker];
				$s .= "$brackets[0] *RECURSION* $brackets[1]";

			} elseif ($level < Debug::$maxDepth || !Debug::$maxDepth) {
				$s .= "<code>$brackets[0]\n";
				$var[$marker] = $brackets;
				foreach ($var as $k => &$v) {
					if ($k === $marker) continue;
					$k = is_int($k) ? $k : '"' . strtr($k, preg_match($reBinary, $k) || preg_last_error() ? $tableBin : $tableUtf) . '"';
					$s .= "$space$space1$k => " . self::htmlDump($v, $level + 1);
				}
				unset($var[$marker]);
				$s .= "$space$brackets[1]</code>";

			} else {
				$s .= "$brackets[0] ... $brackets[1]";
			}
			return $s . "\n";

		} elseif (is_object($var)) {
			$arr = (array) $var;
			$s = "<span>" . get_class($var) . "</span>(" . count($arr) . ") ";
			$space = str_repeat($space1 = '   ', $level);

			static $list = array();
			if (empty($arr)) {

			} elseif (in_array($var, $list, TRUE)) {
				$s .= "{ *RECURSION* }";

			} elseif ($level < Debug::$maxDepth || !Debug::$maxDepth) {
				$s .= "<code>{\n";
				$list[] = $var;
				foreach ($arr as $k => &$v) {
					$m = '';
					if ($k[0] === "\x00") {
						$m = $k[1] === '*' ? ' <span>protected</span>' : ' <span>private</span>';
						$k = substr($k, strrpos($k, "\x00") + 1);
					}
					$k = strtr($k, preg_match($reBinary, $k) || preg_last_error() ? $tableBin : $tableUtf);
					$s .= "$space$space1\"$k\"$m => " . self::htmlDump($v, $level + 1);
				}
				array_pop($list);
				$s .= "$space}</code>";

			} else {
				$s .= "{ ... }";
			}
			return $s . "\n";

		} elseif (is_resource($var)) {
			return "<span>" . get_resource_type($var) . " resource</span>\n";

		} else {
			return "<span>unknown type</span>\n";
		}
	}



	/**
	 * Internal dump() implementation for JSON.
	 * @param  mixed  variable to dump
	 * @param  int    current recursion level
	 * @return string
	 */
	public static function jsonDump(&$var, $level = 0)
	{
		if (is_bool($var) || is_null($var) || is_int($var) || is_float($var)) {
			return $var;

		} elseif (is_string($var)) {
			if (Debug::$maxLen && strlen($var) > Debug::$maxLen) {
				$var = substr($var, 0, Debug::$maxLen) . " \xE2\x80\xA6 ";
			}
			return @iconv('UTF-16', 'UTF-8//IGNORE', iconv('UTF-8', 'UTF-16//IGNORE', $var)); // intentionally @

		} elseif (is_array($var)) {
			static $marker;
			if ($marker === NULL) $marker = uniqid("\x00", TRUE);
			if (isset($var[$marker])) {
				return "\xE2\x80\xA6RECURSION\xE2\x80\xA6";

			} elseif ($level < Debug::$maxDepth || !Debug::$maxDepth) {
				$var[$marker] = TRUE;
				$res = array();
				foreach ($var as $k => &$v) {
					if ($k !== $marker) $res[self::jsonDump($k)] = self::jsonDump($v, $level + 1);
			}
				unset($var[$marker]);
				return $res;

			} else {
				return " \xE2\x80\xA6 ";
		}

		} elseif (is_object($var)) {
			$arr = (array) $var;
			static $list = array();
			if (in_array($var, $list, TRUE)) {
				return "\xE2\x80\xA6RECURSION\xE2\x80\xA6";

			} elseif ($level < Debug::$maxDepth || !Debug::$maxDepth) {
				$list[] = $var;
				$res = array("\x00" => '(object) ' . get_class($var));
				foreach ($arr as $k => &$v) {
					if ($k[0] === "\x00") {
						$k = substr($k, strrpos($k, "\x00") + 1);
					}
					$res[self::jsonDump($k)] = self::jsonDump($v, $level + 1);
				}
				array_pop($list);
				return $res;

			} else {
				return " \xE2\x80\xA6 ";
			}

		} elseif (is_resource($var)) {
			return "resource " . get_resource_type($var);

		} else {
			return "unknown type";
		}
	}



	/**
	 * Dumps variable.
	 * @param  string
	 * @return string
	 */
	public static function clickableDump($dump)
	{
		return '<pre class="nette-dump">' . preg_replace_callback(
			'#^( *)((?>[^(]{1,200}))\((\d+)\) <code>#m',
			function ($m) {
				return "$m[1]<a href='#' rel='next'>$m[2]($m[3]) " . (trim($m[1]) || $m[3] < 7 ? '<abbr>&#x25bc;</abbr> </a><code>' : '<abbr>&#x25ba;</abbr> </a><code class="nette-collapsed">');
			},
			self::htmlDump($dump)
		) . '</pre>';
	}

}
