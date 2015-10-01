<?php

/**
 * This file is part of the Tracy (http://tracy.nette.org)
 * Copyright (c) 2004 David Grudl (http://davidgrudl.com)
 */

namespace Tracy;


/**
 * Rendering helpers for Debugger.
 */
class Helpers
{

	/**
	 * Returns HTML link to editor.
	 * @return string
	 */
	public static function editorLink($file, $line = NULL)
	{
		if ($editor = self::editorUri($file, $line)) {
			$file = strtr($file, '\\', '/');
			if (preg_match('#(^[a-z]:)?/.{1,50}$#i', $file, $m) && strlen($file) > strlen($m[0])) {
				$file = '...' . $m[0];
			}
			$file = strtr($file, '/', DIRECTORY_SEPARATOR);
			return self::formatHtml('<a href="%" title="%">%<b>%</b>%</a>',
				$editor,
				$file . ($line ? ":$line" : ''),
				rtrim(dirname($file), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR,
				basename($file),
				$line ? ":$line" : ''
			);
		} else {
			return self::formatHtml('<span>%</span>', $file . ($line ? ":$line" : ''));
		}
	}


	/**
	 * Returns link to editor.
	 * @return string
	 */
	public static function editorUri($file, $line = NULL)
	{
		if (Debugger::$editor && $file && is_file($file)) {
			return strtr(Debugger::$editor, ['%file' => rawurlencode($file), '%line' => $line ? (int) $line : '']);
		}
	}


	public static function formatHtml($mask, ...$args)
	{
		return preg_replace_callback('#%#', function () use (& $args) {
			return htmlspecialchars(each($args)[1], ENT_IGNORE | ENT_QUOTES, 'UTF-8');
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


	/**
	 * @return string
	 */
	public static function getClass($obj)
	{
		return explode("\x00", get_class($obj))[0];
	}


	/** @internal */
	public static function fixStack($exception)
	{
		if (function_exists('xdebug_get_function_stack')) {
			$stack = [];
			foreach (array_slice(array_reverse(xdebug_get_function_stack()), 2, -1) as $row) {
				$frame = [
					'file' => $row['file'],
					'line' => $row['line'],
					'function' => isset($row['function']) ? $row['function'] : '*unknown*',
					'args' => [],
				];
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


	/** @internal */
	public static function fixEncoding($s)
	{
		return htmlspecialchars_decode(htmlspecialchars($s, ENT_NOQUOTES | ENT_IGNORE, 'UTF-8'), ENT_NOQUOTES);
	}


	/** @internal */
	public static function errorTypeToString($type)
	{
		$types = [
			E_ERROR => 'Fatal Error',
			E_USER_ERROR => 'User Error',
			E_RECOVERABLE_ERROR => 'Recoverable Error',
			E_CORE_ERROR => 'Core Error',
			E_COMPILE_ERROR => 'Compile Error',
			E_PARSE => 'Parse Error',
			E_WARNING => 'Warning',
			E_CORE_WARNING => 'Core Warning',
			E_COMPILE_WARNING => 'Compile Warning',
			E_USER_WARNING => 'User Warning',
			E_NOTICE => 'Notice',
			E_USER_NOTICE => 'User Notice',
			E_STRICT => 'Strict standards',
			E_DEPRECATED => 'Deprecated',
			E_USER_DEPRECATED => 'User Deprecated',
		];
		return isset($types[$type]) ? $types[$type] : 'Unknown error';
	}


	/** @internal */
	public static function getSource()
	{
		if (isset($_SERVER['REQUEST_URI'])) {
			return (!empty($_SERVER['HTTPS']) && strcasecmp($_SERVER['HTTPS'], 'off') ? 'https://' : 'http://')
				. (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '')
				. $_SERVER['REQUEST_URI'];
		} else {
			return empty($_SERVER['argv']) ? 'CLI' : 'CLI: ' . implode(' ', $_SERVER['argv']);
		}
	}

}
