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

	/** @var int[] */
	public $references = [];


	public function describe($var): \stdClass
	{
		uksort($this->objectExposers, function ($a, $b): int {
			return $b === '' || (class_exists($a, false) && is_subclass_of($a, $b)) ? -1 : 1;
		});

		try {
			return (object) [
				'value' => $this->describeVar($var),
				'snapshot' => $this->snapshot,
				'location' => $this->location ? self::findLocation() : null,
			];

		} finally {
			$free = [[], []];
			$this->snapshot = &$free[0];
			$this->references = &$free[1];
		}
	}


	/**
	 * @return mixed
	 */
	private function describeVar($var, int $depth = 0, int $refId = null)
	{
		switch (true) {
			case $var === null:
			case is_bool($var):
			case is_int($var):
				return $var;
			default:
				$m = 'describe' . explode(' ', gettype($var))[0];
				return $this->$m($var, $depth, $refId);
		}
	}


	/**
	 * @return Value|float
	 */
	private function describeDouble(float $num)
	{
		if (!is_finite($num)) {
			return new Value('number', (string) $num);
		}
		$js = json_encode($num);
		return strpos($js, '.')
			? $num
			: new Value('number', "$js.0"); // to distinct int and float in JS
	}


	/**
	 * @return Value|string
	 */
	private function describeString(string $s)
	{
		$encoded = Helpers::encodeString($s, $this->maxLength);
		if ($encoded === $s) {
			return $encoded;
		}
		return new Value('string', $encoded, strlen($s));
	}


	/**
	 * @return Value|array
	 */
	private function describeArray(array $arr, int $depth = 0, int $refId = null)
	{
		if ($refId) {
			$res = new Value('ref', 'p' . $refId);
			$value = &$this->snapshot[$res->value];
			if ($value && $value->depth <= $depth) {
				return $res;
			}

			$value = new Value('array');
			$value->id = $res->value;
			$value->depth = $depth;
			if ($depth >= $this->maxDepth) {
				$value->length = count($arr);
				return $res;
			}
			$items = &$value->items;

		} elseif ($arr && $depth >= $this->maxDepth) {
			return new Value('array', null, count($arr));
		}

		$items = [];
		foreach ($arr as $k => $v) {
			$refId = $this->getReferenceId($arr, $k);
			$items[] = [
				$this->describeKey($k),
				is_string($k) && isset($this->keysToHide[strtolower($k)])
					? new Value('text', self::hideValue($v))
					: $this->describeVar($v, $depth + 1, $refId),
			] + ($refId ? [2 => $refId] : []);
		}

		return $res ?? $items;
	}


	private function describeObject(object $obj, int $depth = 0): Value
	{
		$id = spl_object_id($obj);
		$value = &$this->snapshot[$id];
		if ($value && $value->depth <= $depth) {
			return new Value('ref', $id);
		}

		$value = new Value('object', Helpers::getClass($obj));
		$value->id = $id;
		$value->depth = $depth;
		$value->holder = $obj; // to be not released by garbage collector in collecting mode
		if ($this->location) {
			$rc = $obj instanceof \Closure ? new \ReflectionFunction($obj) : new \ReflectionClass($obj);
			if ($rc->getFileName() && ($editor = Helpers::editorUri($rc->getFileName(), $rc->getStartLine()))) {
				$value->editor = (object) ['file' => $rc->getFileName(), 'line' => $rc->getStartLine(), 'url' => $editor];
			}
		}

		if ($depth < $this->maxDepth) {
			$value->items = [];
			$props = $this->exposeObject($obj, $value);
			foreach ($props ?? [] as $k => $v) {
				$this->addPropertyTo($value, (string) $k, $v, Value::PROP_VIRTUAL, $this->getReferenceId($props, $k));
			}
		}
		return new Value('ref', $id);
	}


	/**
	 * @param  resource  $resource
	 */
	private function describeResource($resource, int $depth = 0): Value
	{
		$id = 'r' . (int) $resource;
		$value = &$this->snapshot[$id];
		if (!$value) {
			$type = is_resource($resource) ? get_resource_type($resource) : 'closed';
			$value = new Value('resource', $type . ' resource');
			$value->id = $id;
			$value->depth = $depth;
			$value->items = [];
			if (isset($this->resourceExposers[$type])) {
				foreach (($this->resourceExposers[$type])($resource) as $k => $v) {
					$value->items[] = [$k, $this->describeVar($v, $depth + 1)];
				}
			}
		}
		return new Value('ref', $id);
	}


	/**
	 * @param  int|string  $key
	 * @return int|string
	 */
	public function describeKey($key)
	{
		return is_int($key) || (preg_match('#^[\w!\#$%&*+./;<>?@^{|}~-]{1,50}$#D', $key) && !preg_match('#^true|false|null$#iD', $key))
			? $key
			: '"' . Helpers::encodeString($key, $this->maxLength) . '"';
	}


	public function addPropertyTo(Value $value, string $k, $v, $type = Value::PROP_VIRTUAL, int $refId = null)
	{
		$v = isset($this->keysToHide[strtolower($k)])
			? new Value('text', self::hideValue($v))
			: $this->describeVar($v, $value->depth + 1, $refId);
		$value->items[] = [$this->describeKey($k), $v, $type] + ($refId ? [3 => $refId] : []);
	}


	private function exposeObject(object $obj, Value $value): ?array
	{
		foreach ($this->objectExposers as $type => $dumper) {
			if (!$type || $obj instanceof $type) {
				return $dumper($obj, $value, $this);
			}
		}

		if ($this->debugInfo && method_exists($obj, '__debugInfo')) {
			return $obj->__debugInfo();
		}

		Exposer::exposeObject($obj, $value, $this);
		return null;
	}


	private static function hideValue($var): string
	{
		return self::HIDDEN_VALUE . ' (' . (is_object($var) ? Helpers::getClass($var) : gettype($var)) . ')';
	}


	public function getReferenceId($arr, $key): ?int
	{
		if (PHP_VERSION_ID >= 70400) {
			if ((!$rr = \ReflectionReference::fromArrayElement($arr, $key))) {
				return null;
			}
			$tmp = &$this->references[$rr->getId()];
			if ($tmp === null) {
				return $tmp = count($this->references);
			}
			return $tmp;
		}
		$uniq = new \stdClass;
		$copy = $arr;
		$orig = $copy[$key];
		$copy[$key] = $uniq;
		if ($arr[$key] !== $uniq) {
			return null;
		}
		$res = array_search($uniq, $this->references, true);
		$copy[$key] = $orig;
		if ($res === false) {
			$this->references[] = &$arr[$key];
			return count($this->references);
		}
		return $res + 1;
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
