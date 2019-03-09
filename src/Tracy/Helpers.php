<?php

/**
 * This file is part of the Tracy (https://tracy.nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Tracy;


/**
 * Rendering helpers for Debugger.
 */
class Helpers
{

	/**
	 * Returns HTML link to editor.
	 */
	public static function editorLink(string $file, int $line = null): string
	{
		$file = strtr($origFile = $file, Debugger::$editorMapping);
		if ($editor = self::editorUri($origFile, $line)) {
			$file = strtr($file, '\\', '/');
			if (preg_match('#(^[a-z]:)?/.{1,40}$#i', $file, $m) && strlen($file) > strlen($m[0])) {
				$file = '...' . $m[0];
			}
			$file = strtr($file, '/', DIRECTORY_SEPARATOR);
			return self::formatHtml('<a href="%" title="%">%<b>%</b>%</a>',
				$editor,
				$origFile . ($line ? ":$line" : ''),
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
	 */
	public static function editorUri(string $file, int $line = null, string $action = 'open', string $search = '', string $replace = ''): ?string
	{
		if (Debugger::$editor && $file && ($action === 'create' || is_file($file))) {
			$file = strtr($file, '/', DIRECTORY_SEPARATOR);
			$file = strtr($file, Debugger::$editorMapping);
			return strtr(Debugger::$editor, [
				'%action' => $action,
				'%file' => rawurlencode($file),
				'%line' => $line ?: 1,
				'%search' => rawurlencode($search),
				'%replace' => rawurlencode($replace),
			]);
		}
		return null;
	}


	public static function formatHtml(string $mask): string
	{
		$args = func_get_args();
		return preg_replace_callback('#%#', function () use (&$args, &$count): string {
			return self::escapeHtml($args[++$count]);
		}, $mask);
	}


	public static function escapeHtml($s): string
	{
		return htmlspecialchars((string) $s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
	}


	public static function findTrace(array $trace, $method, int &$index = null): ?array
	{
		$m = is_array($method) ? $method : explode('::', $method);
		foreach ($trace as $i => $item) {
			if (
				isset($item['function'])
				&& $item['function'] === end($m)
				&& isset($item['class']) === isset($m[1])
				&& (!isset($item['class']) || $m[0] === '*' || is_a($item['class'], $m[0], true))
			) {
				$index = $i;
				return $item;
			}
		}
		return null;
	}


	public static function getClass($obj): string
	{
		return explode("\x00", get_class($obj))[0];
	}


	/** @internal */
	public static function fixStack(\Throwable $exception): \Throwable
	{
		if (function_exists('xdebug_get_function_stack')) {
			$stack = [];
			foreach (array_slice(array_reverse(xdebug_get_function_stack()), 2, -1) as $row) {
				$frame = [
					'file' => $row['file'],
					'line' => $row['line'],
					'function' => $row['function'] ?? '*unknown*',
					'args' => [],
				];
				if (!empty($row['class'])) {
					$frame['type'] = isset($row['type']) && $row['type'] === 'dynamic' ? '->' : '::';
					$frame['class'] = $row['class'];
				}
				$stack[] = $frame;
			}
			$ref = new \ReflectionProperty('Exception', 'trace');
			$ref->setAccessible(true);
			$ref->setValue($exception, $stack);
		}
		return $exception;
	}


	/** @internal */
	public static function fixEncoding(string $s): string
	{
		return htmlspecialchars_decode(htmlspecialchars($s, ENT_NOQUOTES | ENT_IGNORE, 'UTF-8'), ENT_NOQUOTES);
	}


	/** @internal */
	public static function errorTypeToString(int $type): string
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
		return $types[$type] ?? 'Unknown error';
	}


	/** @internal */
	public static function getSource(): string
	{
		if (isset($_SERVER['REQUEST_URI'])) {
			return (!empty($_SERVER['HTTPS']) && strcasecmp($_SERVER['HTTPS'], 'off') ? 'https://' : 'http://')
				. ($_SERVER['HTTP_HOST'] ?? '')
				. $_SERVER['REQUEST_URI'];
		} else {
			return 'CLI (PID: ' . getmypid() . ')'
				. ': ' . implode(' ', array_map([self::class, 'escapeArg'], $_SERVER['argv']));
		}
	}


	/** @internal */
	public static function improveException(\Throwable $e): void
	{
		$message = $e->getMessage();
		if (!$e instanceof \Error && !$e instanceof \ErrorException) {
			// do nothing
		} elseif (preg_match('#^Call to undefined function (\S+\\\\)?(\w+)\(#', $message, $m)) {
			$funcs = array_merge(get_defined_functions()['internal'], get_defined_functions()['user']);
			$hint = self::getSuggestion($funcs, $m[1] . $m[2]) ?: self::getSuggestion($funcs, $m[2]);
			$message = "Call to undefined function $m[2](), did you mean $hint()?";
			$replace = ["$m[2](", "$hint("];

		} elseif (preg_match('#^Call to undefined method ([\w\\\\]+)::(\w+)#', $message, $m)) {
			$hint = self::getSuggestion(get_class_methods($m[1]) ?: [], $m[2]);
			$message .= ", did you mean $hint()?";
			$replace = ["$m[2](", "$hint("];

		} elseif (preg_match('#^Undefined variable: (\w+)#', $message, $m) && !empty($e->context)) {
			$hint = self::getSuggestion(array_keys($e->context), $m[1]);
			$message = "Undefined variable $$m[1], did you mean $$hint?";
			$replace = ["$$m[1]", "$$hint"];

		} elseif (preg_match('#^Undefined property: ([\w\\\\]+)::\$(\w+)#', $message, $m)) {
			$rc = new \ReflectionClass($m[1]);
			$items = array_diff($rc->getProperties(\ReflectionProperty::IS_PUBLIC), $rc->getProperties(\ReflectionProperty::IS_STATIC));
			$hint = self::getSuggestion($items, $m[2]);
			$message .= ", did you mean $$hint?";
			$replace = ["->$m[2]", "->$hint"];

		} elseif (preg_match('#^Access to undeclared static property: ([\w\\\\]+)::\$(\w+)#', $message, $m)) {
			$rc = new \ReflectionClass($m[1]);
			$items = array_intersect($rc->getProperties(\ReflectionProperty::IS_PUBLIC), $rc->getProperties(\ReflectionProperty::IS_STATIC));
			$hint = self::getSuggestion($items, $m[2]);
			$message .= ", did you mean $$hint?";
			$replace = ["::$$m[2]", "::$$hint"];
		}

		if (isset($hint)) {
			$ref = new \ReflectionProperty($e, 'message');
			$ref->setAccessible(true);
			$ref->setValue($e, $message);
			$e->tracyAction = [
				'link' => self::editorUri($e->getFile(), $e->getLine(), 'fix', $replace[0], $replace[1]),
				'label' => 'fix it',
			];
		}
	}


	/** @internal */
	public static function improveError(string $message, array $context = []): string
	{
		if (preg_match('#^Undefined variable: (\w+)#', $message, $m) && $context) {
			$hint = self::getSuggestion(array_keys($context), $m[1]);
			return $hint ? "Undefined variable $$m[1], did you mean $$hint?" : $message;

		} elseif (preg_match('#^Undefined property: ([\w\\\\]+)::\$(\w+)#', $message, $m)) {
			$rc = new \ReflectionClass($m[1]);
			$items = array_diff($rc->getProperties(\ReflectionProperty::IS_PUBLIC), $rc->getProperties(\ReflectionProperty::IS_STATIC));
			$hint = self::getSuggestion($items, $m[2]);
			return $hint ? $message . ", did you mean $$hint?" : $message;
		}
		return $message;
	}


	/** @internal */
	public static function guessClassFile(string $class): ?string
	{
		$segments = explode(DIRECTORY_SEPARATOR, $class);
		$res = null;
		$max = 0;
		foreach (get_declared_classes() as $class) {
			$parts = explode(DIRECTORY_SEPARATOR, $class);
			foreach ($parts as $i => $part) {
				if ($part !== $segments[$i] ?? null) {
					break;
				}
			}
			if ($i > $max && ($file = (new \ReflectionClass($class))->getFileName())) {
				$max = $i;
				$res = array_merge(array_slice(explode(DIRECTORY_SEPARATOR, $file), 0, $i - count($parts)), array_slice($segments, $i));
				$res = implode(DIRECTORY_SEPARATOR, $res) . '.php';
			}
		}
		return $res;
	}


	/**
	 * Finds the best suggestion.
	 * @internal
	 */
	public static function getSuggestion(array $items, string $value): ?string
	{
		$best = null;
		$min = (strlen($value) / 4 + 1) * 10 + .1;
		foreach (array_unique($items, SORT_REGULAR) as $item) {
			$item = is_object($item) ? $item->getName() : $item;
			if (($len = levenshtein($item, $value, 10, 11, 10)) > 0 && $len < $min) {
				$min = $len;
				$best = $item;
			}
		}
		return $best;
	}


	/** @internal */
	public static function isHtmlMode(): bool
	{
		return empty($_SERVER['HTTP_X_REQUESTED_WITH']) && empty($_SERVER['HTTP_X_TRACY_AJAX'])
			&& PHP_SAPI !== 'cli'
			&& !preg_match('#^Content-Type: (?!text/html)#im', implode("\n", headers_list()));
	}


	/** @internal */
	public static function isAjax(): bool
	{
		return isset($_SERVER['HTTP_X_TRACY_AJAX']) && preg_match('#^\w{10,15}\z#', $_SERVER['HTTP_X_TRACY_AJAX']);
	}


	/** @internal */
	public static function getNonce(): ?string
	{
		return preg_match('#^Content-Security-Policy(?:-Report-Only)?:.*\sscript-src\s+(?:[^;]+\s)?\'nonce-([\w+/]+=*)\'#mi', implode("\n", headers_list()), $m)
			? $m[1]
			: null;
	}


	/**
	 * Escape a string to be used as a shell argument.
	 */
	private static function escapeArg(string $s): string
	{
		if (preg_match('#^[a-z0-9._=/:-]+\z#i', $s)) {
			return $s;
		}

		return defined('PHP_WINDOWS_VERSION_BUILD')
			? '"' . str_replace('"', '""', $s) . '"'
			: escapeshellarg($s);
	}
}
