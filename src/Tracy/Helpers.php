<?php

/**
 * This file is part of the Tracy (http://tracy.nette.org)
 * Copyright (c) 2004 David Grudl (http://davidgrudl.com)
 */

namespace Tracy;

use Tracy;


/**
 * Rendering helpers for Debugger.
 *
 * @author     David Grudl
 */
class Helpers
{

	/**
	 * Returns link to editor.
	 * @return string
	 */
	public static function editorLink($file, $line)
	{
		if (Debugger::$editor && is_file($file)) {
			$dir = dirname(strtr($file, '/', DIRECTORY_SEPARATOR));
			$base = isset($_SERVER['SCRIPT_FILENAME']) ? dirname(dirname(strtr($_SERVER['SCRIPT_FILENAME'], '/', DIRECTORY_SEPARATOR))) : dirname($dir);
			if (substr($dir, 0, strlen($base)) === $base) {
				$dir = '...' . substr($dir, strlen($base));
			}
			return self::createHtml('<a href="%" title="%">%<b>%</b>%</a>',
				strtr(Debugger::$editor, array('%file' => rawurlencode($file), '%line' => $line)),
				"$file:$line",
				rtrim($dir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR,
				basename($file),
				$line ? ":$line" : ''
			);
		} else {
			return self::createHtml('<span>%</span>', $file . ($line ? ":$line" : ''));
		}
	}


	public static function createHtml($mask)
	{
		$args = func_get_args();
		return preg_replace_callback('#%#', function() use (& $args, & $count) {
			return htmlspecialchars($args[++$count]);
		}, $mask);
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


	public static function fixStack($exception)
	{
		if (function_exists('xdebug_get_function_stack')) {
			$stack = array();
			foreach (array_slice(array_reverse(xdebug_get_function_stack()), 2, -1) as $row) {
				$frame = array(
					'file' => $row['file'],
					'line' => $row['line'],
					'function' => isset($row['function']) ? $row['function'] : '*unknown*',
					'args' => array(),
				);
				if (!empty($row['class'])) {
					$frame['type'] = isset($row['type']) && $row['type'] === 'dynamic' ? '->' : '::';
					$frame['class'] = $row['class'];
				}
				$stack[] = $frame;
			}
			$ref = new \ReflectionProperty('Exception', 'trace');
			$ref->setAccessible(TRUE);
			$ref->setValue($exception, $stack);
		}
		return $exception;
	}


	/**
	 * Returns correctly UTF-8 encoded string.
	 * @param  string  byte stream to fix
	 * @return string
	 */
	public static function fixEncoding($s)
	{
		if (PHP_VERSION_ID < 50400) {
			return @iconv('UTF-16', $encoding . '//IGNORE', iconv($encoding, 'UTF-16//IGNORE', $s)); // intentionally @
		} else {
			return htmlspecialchars_decode(htmlspecialchars($s, ENT_NOQUOTES | ENT_IGNORE, 'UTF-8'), ENT_NOQUOTES);
		}
	}

}
