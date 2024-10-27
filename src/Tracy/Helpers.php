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
	public static function editorLink(string $file, ?int $line = null): string
	{
		$file = strtr($origFile = $file, Debugger::$editorMapping);
		if ($editor = self::editorUri($origFile, $line)) {
			$parts = explode('/', strtr($file, '\\', '/'));
			$file = array_pop($parts);
			while ($parts && strlen($file) < 50) {
				$file = array_pop($parts) . '/' . $file;
			}

			$file = ($parts ? '.../' : '') . $file;
			$file = strtr($file, '/', DIRECTORY_SEPARATOR);

			return self::formatHtml(
				'<a href="%" title="%" class="tracy-editor">%<b>%</b>%</a>',
				$editor,
				$origFile . ($line ? ":$line" : ''),
				rtrim(dirname($file), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR,
				basename($file),
				$line ? ":$line" : '',
			);
		} else {
			return self::formatHtml('<span>%</span>', $file . ($line ? ":$line" : ''));
		}
	}


	/**
	 * Returns link to editor.
	 */
	public static function editorUri(
		string $file,
		?int $line = null,
		string $action = 'open',
		string $search = '',
		string $replace = '',
	): ?string
	{
		if (Debugger::$editor && $file && ($action === 'create' || @is_file($file))) { // @ - may trigger error
			$file = strtr($file, '/', DIRECTORY_SEPARATOR);
			$file = strtr($file, Debugger::$editorMapping);
			$search = str_replace("\n", PHP_EOL, $search);
			$replace = str_replace("\n", PHP_EOL, $replace);
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
			return str_replace("\n", '&#10;', self::escapeHtml($args[++$count]));
		}, $mask);
	}


	public static function escapeHtml(mixed $s): string
	{
		return htmlspecialchars((string) $s, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8');
	}


	public static function htmlToText(string $s): string
	{
		return htmlspecialchars_decode(strip_tags($s), ENT_QUOTES | ENT_HTML5);
	}


	public static function findTrace(array $trace, array|string $method, ?int &$index = null): ?array
	{
		$m = is_array($method) ? $method : explode('::', $method);
		foreach ($trace as $i => $item) {
			if (
				isset($item['function'])
				&& $item['function'] === end($m)
				&& isset($item['class']) === isset($m[1])
				&& (!isset($item['class']) || $m[0] === '*' || is_a($item['class'], $m[0], allow_string: true))
			) {
				$index = $i;
				return $item;
			}
		}

		return null;
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
			E_DEPRECATED => 'Deprecated',
			E_USER_DEPRECATED => 'User Deprecated',
		];
		return $types[$type] ?? 'Unknown error';
	}


	/** @internal */
	public static function getSource(): string
	{
		if (self::isCli()) {
			return 'CLI (PID: ' . getmypid() . ')'
				. (isset($_SERVER['argv']) ? ': ' . implode(' ', array_map([self::class, 'escapeArg'], $_SERVER['argv'])) : '');

		} elseif (isset($_SERVER['REQUEST_URI'])) {
			return (!empty($_SERVER['HTTPS']) && strcasecmp($_SERVER['HTTPS'], 'off') ? 'https://' : 'http://')
				. ($_SERVER['HTTP_HOST'] ?? '')
				. $_SERVER['REQUEST_URI'];

		} else {
			return PHP_SAPI;
		}
	}


	/** @internal */
	public static function improveException(\Throwable $e): void
	{
		$message = $e->getMessage();
		if (
			!($e instanceof \Error || $e instanceof \ErrorException)
			|| str_contains($e->getMessage(), 'did you mean')
		) {
			// do nothing
		} elseif (preg_match('~Argument #(\d+)(?: \(\$\w+\))? must be of type callable, (.+ given)~', $message, $m)) {
			$arg = $e->getTrace()[0]['args'][$m[1] - 1] ?? null;
			if (is_string($arg) && str_contains($arg, '::')) {
				$arg = explode('::', $arg, 2);
			}
			if (!is_callable($arg, syntax_only: true)) {
				// do nothing
			} elseif (is_array($arg) && is_string($arg[0]) && !class_exists($arg[0]) && !trait_exists($arg[0])) {
				$message = str_replace($m[2], "but class '$arg[0]' does not exist", $message);
			} elseif (is_array($arg) && !method_exists($arg[0], $arg[1])) {
				$hint = self::getSuggestion(get_class_methods($arg[0]) ?: [], $arg[1]);
				$class = is_object($arg[0]) ? get_class($arg[0]) : $arg[0];
				$message = str_replace($m[2], "but method $class::$arg[1]() does not exist" . ($hint ? " (did you mean $hint?)" : ''), $message);
			} elseif (is_string($arg) && !function_exists($arg)) {
				$funcs = array_merge(get_defined_functions()['internal'], get_defined_functions()['user']);
				$hint = self::getSuggestion($funcs, $arg);
				$message = str_replace($m[2], "but function '$arg' does not exist" . ($hint ? " (did you mean $hint?)" : ''), $message);
			}

		} elseif (preg_match('#^Call to undefined function (\S+\\\\)?(\w+)\(#', $message, $m)) {
			$funcs = array_merge(get_defined_functions()['internal'], get_defined_functions()['user']);
			if ($hint = self::getSuggestion($funcs, $m[1] . $m[2]) ?: self::getSuggestion($funcs, $m[2])) {
				$message = "Call to undefined function $m[2](), did you mean $hint()?";
				$replace = ["$m[2](", "$hint("];
			}

		} elseif (preg_match('#^Call to undefined method ([\w\\\\]+)::(\w+)#', $message, $m)) {
			if ($hint = self::getSuggestion(get_class_methods($m[1]) ?: [], $m[2])) {
				$message .= ", did you mean $hint()?";
				$replace = ["$m[2](", "$hint("];
			}

		} elseif (preg_match('#^Undefined property: ([\w\\\\]+)::\$(\w+)#', $message, $m)) {
			$rc = new \ReflectionClass($m[1]);
			$items = array_filter($rc->getProperties(\ReflectionProperty::IS_PUBLIC), fn($prop) => !$prop->isStatic());
			if ($hint = self::getSuggestion($items, $m[2])) {
				$message .= ", did you mean $$hint?";
				$replace = ["->$m[2]", "->$hint"];
			}

		} elseif (preg_match('#^Access to undeclared static property:? ([\w\\\\]+)::\$(\w+)#', $message, $m)) {
			$rc = new \ReflectionClass($m[1]);
			$items = array_filter($rc->getProperties(\ReflectionProperty::IS_STATIC), fn($prop) => $prop->isPublic());
			if ($hint = self::getSuggestion($items, $m[2])) {
				$message .= ", did you mean $$hint?";
				$replace = ["::$$m[2]", "::$$hint"];
			}
		}

		if ($message !== $e->getMessage()) {
			$ref = new \ReflectionProperty($e, 'message');
			$ref->setAccessible(true);
			$ref->setValue($e, $message);
		}

		if (isset($replace)) {
			$loc = Debugger::mapSource($e->getFile(), $e->getLine()) ?? ['file' => $e->getFile(), 'line' => $e->getLine()];
			@$e->tracyAction = [ // dynamic properties are deprecated since PHP 8.2
				'link' => self::editorUri($loc['file'], $loc['line'], 'fix', $replace[0], $replace[1]),
				'label' => 'fix it',
			];
		}
	}


	/** @internal */
	public static function improveError(string $message): string
	{
		if (preg_match('#^Undefined property: ([\w\\\\]+)::\$(\w+)#', $message, $m)) {
			$rc = new \ReflectionClass($m[1]);
			$items = array_filter($rc->getProperties(\ReflectionProperty::IS_PUBLIC), fn($prop) => !$prop->isStatic());
			$hint = self::getSuggestion($items, $m[2]);
			return $hint ? $message . ", did you mean $$hint?" : $message;
		}

		return $message;
	}


	/** @internal */
	public static function guessClassFile(string $class): ?string
	{
		$segments = explode('\\', $class);
		$res = null;
		$max = 0;
		foreach (get_declared_classes() as $class) {
			$parts = explode('\\', $class);
			foreach ($parts as $i => $part) {
				if ($part !== ($segments[$i] ?? null)) {
					break;
				}
			}

			if ($i > $max && $i < count($segments) && ($file = (new \ReflectionClass($class))->getFileName())) {
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
		$items = array_map(fn($item) => $item instanceof \Reflector ? $item->getName() : (string) $item, $items);
		foreach (array_unique($items) as $item) {
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
		return empty($_SERVER['HTTP_X_REQUESTED_WITH'])
			&& empty($_SERVER['HTTP_X_TRACY_AJAX'])
			&& isset($_SERVER['HTTP_HOST'])
			&& !self::isCli()
			&& !preg_match('#^Content-Type: *+(?!text/html)#im', implode("\n", headers_list()));
	}


	/** @internal */
	public static function isAjax(): bool
	{
		return isset($_SERVER['HTTP_X_TRACY_AJAX']) && preg_match('#^\w{10,15}$#D', $_SERVER['HTTP_X_TRACY_AJAX']);
	}


	/** @internal */
	public static function isRedirect(): bool
	{
		return (bool) preg_match('#^Location:#im', implode("\n", headers_list()));
	}


	/** @internal */
	public static function createId(): string
	{
		return bin2hex(random_bytes(5));
	}


	/** @internal */
	public static function isCli(): bool
	{
		return PHP_SAPI === 'cli' || PHP_SAPI === 'phpdbg';
	}


	/** @internal */
	public static function getNonceAttr(): string
	{
		return preg_match('#^Content-Security-Policy(?:-Report-Only)?:.*\sscript-src\s+(?:[^;]+\s)?\'nonce-([\w+/]+=*)\'#mi', implode("\n", headers_list()), $m)
			? ' nonce="' . self::escapeHtml($m[1]) . '"'
			: '';
	}


	/**
	 * Escape a string to be used as a shell argument.
	 */
	private static function escapeArg(string $s): string
	{
		if (preg_match('#^[a-z0-9._=/:-]+$#Di', $s)) {
			return $s;
		}

		return defined('PHP_WINDOWS_VERSION_BUILD')
			? '"' . str_replace('"', '""', $s) . '"'
			: escapeshellarg($s);
	}


	/**
	 * Captures PHP output into a string.
	 */
	public static function capture(callable $func): string
	{
		ob_start(fn() => null);
		try {
			$func();
			return ob_get_clean();
		} catch (\Throwable $e) {
			ob_end_clean();
			throw $e;
		}
	}


	/** @internal */
	public static function encodeString(string $s, ?int $maxLength = null, bool $showWhitespaces = true): string
	{
		$utf8 = self::isUtf8($s);
		$len = $utf8 ? self::utf8Length($s) : strlen($s);
		return $maxLength && $len > $maxLength + 20
			? self::doEncodeString(self::truncateString($s, $maxLength, $utf8), $utf8, $showWhitespaces)
				. ' <span>…</span> '
				. self::doEncodeString(self::truncateString($s, -10, $utf8), $utf8, $showWhitespaces)
			: self::doEncodeString($s, $utf8, $showWhitespaces);
	}


	private static function doEncodeString(string $s, bool $utf8, bool $showWhitespaces): string
	{
		$specials = [
			true => [
				"\r" => '<i>\r</i>',
				"\n" => "<i>\\n</i>\n",
				"\t" => '<i>\t</i>    ',
				"\e" => '<i>\e</i>',
				'<' => '&lt;',
				'&' => '&amp;',
			],
			false => [
				"\r" => "\r",
				"\n" => "\n",
				"\t" => "\t",
				"\e" => '<i>\e</i>',
				'<' => '&lt;',
				'&' => '&amp;',
			],
		];
		$special = $specials[$showWhitespaces];
		$s = preg_replace_callback(
			$utf8 ? '#[\p{C}<&]#u' : '#[\x00-\x1F\x7F-\xFF<&]#',
			fn($m) => $special[$m[0]] ?? (strlen($m[0]) === 1
					? '<i>\x' . str_pad(strtoupper(dechex(ord($m[0]))), 2, '0', STR_PAD_LEFT) . '</i>'
					: '<i>\u{' . strtoupper(ltrim(dechex(self::utf8Ord($m[0])), '0')) . '}</i>'),
			$s,
		);
		$s = str_replace('</i><i>', '', $s);
		$s = preg_replace('~\n$~D', '', $s);
		return $s;
	}


	private static function utf8Ord(string $c): int
	{
		$ord0 = ord($c[0]);
		if ($ord0 < 0x80) {
			return $ord0;
		} elseif ($ord0 < 0xE0) {
			return ($ord0 << 6) + ord($c[1]) - 0x3080;
		} elseif ($ord0 < 0xF0) {
			return ($ord0 << 12) + (ord($c[1]) << 6) + ord($c[2]) - 0xE2080;
		} else {
			return ($ord0 << 18) + (ord($c[1]) << 12) + (ord($c[2]) << 6) + ord($c[3]) - 0x3C82080;
		}
	}


	/** @internal */
	public static function utf8Length(string $s): int
	{
		return match (true) {
			extension_loaded('mbstring') => mb_strlen($s, 'UTF-8'),
			extension_loaded('iconv') => iconv_strlen($s, 'UTF-8'),
			default => strlen(@utf8_decode($s)), // deprecated
		};
	}


	/** @internal */
	public static function isUtf8(string $s): bool
	{
		return (bool) preg_match('##u', $s);
	}


	/** @internal */
	public static function truncateString(string $s, int $len, bool $utf8): string
	{
		if (!$utf8) {
			return $len < 0 ? substr($s, $len) : substr($s, 0, $len);
		} elseif (function_exists('mb_substr')) {
			return $len < 0
				? mb_substr($s, $len, -$len, 'UTF-8')
				: mb_substr($s, 0, $len, 'UTF-8');
		} else {
			$len < 0
				? preg_match('#.{0,' . -$len . '}\z#us', $s, $m)
				: preg_match("#^.{0,$len}#us", $s, $m);
			return $m[0];
		}
	}


	/** @internal */
	public static function htmlToAnsi(string $s, array $colors): string
	{
		$stack = ['0'];
		$s = preg_replace_callback(
			'#<\w+(?: class=["\']tracy-(?:dump-)?([\w-]+)["\'])?[^>]*>|</\w+>#',
			function ($m) use ($colors, &$stack): string {
				if ($m[0][1] === '/') {
					array_pop($stack);
				} else {
					$stack[] = isset($m[1], $colors[$m[1]]) ? $colors[$m[1]] : '0';
				}
				return "\e[" . end($stack) . 'm';
			},
			$s,
		);
		$s = preg_replace('/\e\[0m( *)(?=\e)/', '$1', $s);
		$s = self::htmlToText($s);
		return $s;
	}


	/** @internal */
	public static function minifyJs(string $s): string
	{
		// author: Jakub Vrana https://php.vrana.cz/minifikace-javascriptu.php
		$last = '';
		return preg_replace_callback(
			<<<'XX'
				(
					(?:
						(^|[-+\([{}=,:;!%^&*|?~]|/(?![/*])|return|throw) # context before regexp
						(?:\s|//[^\n]*+\n|/\*(?:[^*]|\*(?!/))*+\*/)* # optional space
						(/(?![/*])(?:\\[^\n]|[^[\n/\\]|\[(?:\\[^\n]|[^]])++)+/) # regexp
						|(^
							|'(?:\\.|[^\n'\\])*'
							|"(?:\\.|[^\n"\\])*"
							|([0-9A-Za-z_$]+)
							|([-+]+)
							|.
						)
					)(?:\s|//[^\n]*+\n|/\*(?:[^*]|\*(?!/))*+\*/)* # optional space
				())sx
				XX,
			function ($match) use (&$last) {
				[, $context, $regexp, $result, $word, $operator] = $match;
				if ($word !== '') {
					$result = ($last === 'word' ? ' ' : ($last === 'return' ? ' ' : '')) . $result;
					$last = ($word === 'return' || $word === 'throw' || $word === 'break' ? 'return' : 'word');
				} elseif ($operator) {
					$result = ($last === $operator[0] ? ' ' : '') . $result;
					$last = $operator[0];
				} else {
					if ($regexp) {
						$result = $context . ($context === '/' ? ' ' : '') . $regexp;
					}

					$last = '';
				}

				return $result;
			},
			$s . "\n",
		);
	}


	/** @internal */
	public static function minifyCss(string $s): string
	{
		$last = '';
		return preg_replace_callback(
			<<<'XX'
				(
					(^
						|'(?:\\.|[^\n'\\])*'
						|"(?:\\.|[^\n"\\])*"
						|([0-9A-Za-z_*#.%:()[\]-]+)
						|.
					)(?:\s|/\*(?:[^*]|\*(?!/))*+\*/)* # optional space
				())sx
				XX,
			function ($match) use (&$last) {
				[, $result, $word] = $match;
				if ($last === ';') {
					$result = $result === '}' ? '}' : ';' . $result;
					$last = '';
				}

				if ($word !== '') {
					$result = ($last === 'word' ? ' ' : '') . $result;
					$last = 'word';
				} elseif ($result === ';') {
					$last = ';';
					$result = '';
				} else {
					$last = '';
				}

				return $result;
			},
			$s . "\n",
		);
	}


	public static function detectColors(): bool
	{
		return self::isCli()
			&& getenv('NO_COLOR') === false // https://no-color.org
			&& (getenv('FORCE_COLOR')
				|| (function_exists('sapi_windows_vt100_support')
					? sapi_windows_vt100_support(STDOUT)
					: @stream_isatty(STDOUT)) // @ may trigger error 'cannot cast a filtered stream on this system'
			);
	}


	public static function getExceptionChain(\Throwable $ex): array
	{
		$res = [$ex];
		while (($ex = $ex->getPrevious()) && !in_array($ex, $res, true)) {
			$res[] = $ex;
		}

		return $res;
	}


	public static function traverseValue(mixed $val, callable $callback, array &$skip = [], ?string $refId = null): void
	{
		if (is_object($val)) {
			$id = spl_object_id($val);
			if (!isset($skip[$id])) {
				$skip[$id] = true;
				$callback($val);
				self::traverseValue((array) $val, $callback, $skip);
			}

		} elseif (is_array($val)) {
			if ($refId) {
				if (isset($skip[$refId])) {
					return;
				}
				$skip[$refId] = true;
			}

			foreach ($val as $k => $v) {
				$refId = \ReflectionReference::fromArrayElement($val, $k)?->getId();
				self::traverseValue($v, $callback, $skip, $refId);
			}
		}
	}


	/** @internal */
	public static function decomposeFlags(int $flags, bool $set, array $constants): ?array
	{
		$res = null;
		foreach ($constants as $constant) {
			if (defined($constant)) {
				$v = constant($constant);
				if ($set) {
					if ($v && ($flags & $v) === $v) {
						$res[] = $constant;
						$flags &= ~$v;
					}
				} elseif ($flags === $v) {
					return [$constant];
				}
			}
		}

		if ($flags && $res && $set) {
			$res[] = (string) $flags;
		}
		return $res;
	}
}
