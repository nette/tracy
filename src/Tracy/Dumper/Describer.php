<?php

/**
 * This file is part of the Tracy (https://tracy.nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Tracy\Dumper;

use Tracy\Helpers;


/**
 * Converts PHP values to internal representation.
 * @internal
 */
final class Describer
{
	public const HIDDEN_VALUE = '*****';

	/** @var int */
	public $maxDepth = 4;

	/** @var int */
	public $maxLength = 150;

	/** @var Value[] */
	public $snapshot = [];

	/** @var bool */
	public $debugInfo = false;

	/** @var array */
	public $keysToHide = [];

	/** @var bool */
	public $location = false;

	/** @var callable[] */
	public $resourceExposers;

	/** @var callable[] */
	public $objectExposers;


	public function describe(&$var): \stdClass
	{
		uksort($this->objectExposers, function ($a, $b): int {
			return $b === '' || (class_exists($a, false) && is_subclass_of($a, $b)) ? -1 : 1;
		});

		try {
			return (object) [
				'value' => $this->toJson($var),
				'snapshot' => $this->snapshot,
				'location' => $this->location ? self::findLocation() : null,
			];

		} finally {
			$free = [];
			$this->snapshot = &$free;
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
				? (strpos($tmp = json_encode($var), '.') ? $var : new Value('number', "$tmp.0"))
				: new Value('number', (string) $var);

		} elseif (is_string($var)) {
			$s = Helpers::encodeString($var, $this->maxLength);
			if ($s === $var) {
				return $s;
			}
			return new Value('string', $s, strlen($var));

		} elseif (is_array($var)) {
			static $marker;
			if ($marker === null) {
				$marker = uniqid("\x00", true);
			}
			if (count($var) && (isset($var[$marker]) || $depth >= $this->maxDepth)) {
				return new Value('stop', [count($var) - isset($var[$marker]), isset($var[$marker])]);
			}
			$res = [];
			try {
				$var[$marker] = true;
				foreach ($var as $k => &$v) {
					if ($k !== $marker) {
						$res[] = [
							$this->encodeKey($k),
							is_string($k) && isset($this->keysToHide[strtolower($k)])
								? new Value('text', self::hideValue($v))
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
			if (!$obj) {
				$obj = new Value('object', Helpers::getClass($var));
				$obj->id = $id;
				$obj->depth = $depth;
				$obj->holder = $var; // to be not released by garbage collector in collecting mode
				if ($this->location) {
					$rc = $var instanceof \Closure ? new \ReflectionFunction($var) : new \ReflectionClass($var);
					if ($rc->getFileName() && ($editor = Helpers::editorUri($rc->getFileName(), $rc->getStartLine()))) {
						$obj->editor = (object) ['file' => $rc->getFileName(), 'line' => $rc->getStartLine(), 'url' => $editor];
					}
				}
			} elseif ($obj->depth <= $depth) {
				return new Value('ref', $id);
			}

			if ($depth < $this->maxDepth || !$this->maxDepth) {
				$obj->depth = $depth;
				$obj->items = [];

				foreach ($this->exportObject($var) as $k => $v) {
					$vis = 0;
					$k = (string) $k;
					if (isset($k[0]) && $k[0] === "\x00") {
						$vis = $k[1] === '*' ? 1 : 2;
						$k = substr($k, strrpos($k, "\x00") + 1);
					}
					$obj->items[] = [
						$this->encodeKey($k),
						isset($this->keysToHide[strtolower($k)])
							? new Value('text', self::hideValue($v))
							: $this->toJson($v, $depth + 1),
						$vis,
					];
				}
			}
			return new Value('ref', $id);

		} else {
			$id = 'r' . (int) $var;
			$obj = &$this->snapshot[$id];
			if (!$obj) {
				$type = is_resource($var) ? get_resource_type($var) : 'closed';
				$obj = new Value('resource', $type . ' resource');
				$obj->id = $id;
				$obj->depth = $depth;
				$obj->items = [];
				if (isset($this->resourceExposers[$type])) {
					foreach (($this->resourceExposers[$type])($var) as $k => $v) {
						$obj->items[] = [$k, $this->toJson($v, $depth + 1)];
					}
				}
			}
			return new Value('ref', $id);
		}
	}


	/**
	 * @param  int|string  $key
	 * @return int|string
	 */
	private function encodeKey($key)
	{
		return is_int($key) || (preg_match('#^[!\#$%&()*+,./0-9:;<=>?@A-Z[\]^_`a-z{|}~-]{1,50}$#D', $key) && !preg_match('#^true|false|null$#iD', $key))
			? $key
			: '"' . Helpers::encodeString($key, $this->maxLength) . '"';
	}


	private function exportObject(object $obj): array
	{
		foreach ($this->objectExposers as $type => $dumper) {
			if (!$type || $obj instanceof $type) {
				return $dumper($obj);
			}
		}

		if ($this->debugInfo && method_exists($obj, '__debugInfo')) {
			return $obj->__debugInfo();
		}

		return (array) $obj;
	}


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
			if (isset($item['class']) && ($item['class'] === self::class || $item['class'] === \Tracy\Dumper::class)) {
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
}
