<?php

/**
 * This file is part of the Tracy (https://tracy.nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Tracy;


/**
 * Converts PHP values to internal representation.
 * @internal
 */
class Describer
{
	/** @var int|null */
	public $maxDepth = 4;

	/** @var int|null */
	public $maxLength = 150;

	/** @var bool */
	public $location = false;

	/** @var array */
	public $snapshot = [];

	/** @var bool */
	public $debugInfo = false;

	/** @var array */
	public $keysToHide = [];

	/** @var callable[] */
	public $resourceExposers;

	/** @var callable[] */
	public $objectExposers;


	/**
	 * @return mixed
	 */
	public function describe(&$var)
	{
		uksort($this->objectExposers, function ($a, $b): int {
			return $b === '' || (class_exists($a, false) && is_subclass_of($a, $b)) ? -1 : 1;
		});
		return $this->describeVar($var);
	}


	/**
	 * @return mixed
	 */
	private function describeVar(&$var, int $depth = 0)
	{
		switch (true) {
			case $var === null:
			case is_bool($var):
			case is_int($var):
				return $var;

			case is_float($var):
				return is_finite($var)
					? (strpos($tmp = json_encode($var), '.') ? $var : (object) ['number' => "$tmp.0"])
					: (object) ['number' => (string) $var];

			case is_string($var):
				$s = Helpers::encodeString($var, $this->maxLength);
				if ($s === $var) {
					return $s;
				}
				return (object) ['string' => $s, 'length' => strlen($var)];

			case is_array($var):
				return $this->describeArray($var, $depth);

			case is_object($var):
				return $this->describeObject($var, $depth);

			case is_resource($var):
				return $this->describeResource($var, $depth);

			default:
				throw new \Exception('Unknown type');
		}
	}


	/**
	 * @return object|array
	 */
	private function describeArray(array &$arr, int $depth = 0)
	{
		static $marker;
		if ($marker === null) {
			$marker = uniqid("\x00", true);
		}
		if (count($arr) && (isset($arr[$marker]) || $depth >= $this->maxDepth)) {
			return (object) ['stop' => [count($arr) - isset($arr[$marker]), isset($arr[$marker])]];
		}
		$res = [];
		try {
			$arr[$marker] = true;
			foreach ($arr as $k => &$v) {
				if ($k !== $marker) {
					$res[] = [
						$this->encodeKey($k),
						is_string($k) && isset($this->keysToHide[strtolower($k)])
							? (object) ['key' => self::hideValue($v)]
							: $this->describeVar($v, $depth + 1),
					];
				}
			}
		} finally {
			unset($arr[$marker]);
		}
		return $res;
	}


	private function describeObject(object $obj, int $depth = 0): object
	{
		$id = spl_object_id($obj);
		$shot = &$this->snapshot[$id];
		if ($shot && $shot->depth <= $depth) {
			return (object) ['object' => $id];
		}

		$shot = $shot ?: (object) [
			'name' => Helpers::getClass($obj),
			'depth' => $depth,
			'object' => $obj, // to be not released by garbage collector
		];
		if (empty($shot->editor) && $this->location) {
			$rc = $obj instanceof \Closure ? new \ReflectionFunction($obj) : new \ReflectionClass($obj);
			if ($editor = $rc->getFileName() ? Helpers::editorUri($rc->getFileName(), $rc->getStartLine()) : null) {
				$shot->editor = (object) ['file' => $rc->getFileName(), 'line' => $rc->getStartLine(), 'url' => $editor];
			}
		}

		if ($depth < $this->maxDepth || !$this->maxDepth) {
			$shot->depth = $depth;
			$shot->items = [];

			foreach ($this->exposeObject($obj) as $k => $v) {
				$visibility = 0;
				if (isset($k[0]) && $k[0] === "\x00") {
					$visibility = $k[1] === '*' ? 1 : 2;
					$k = substr($k, strrpos($k, "\x00") + 1);
				}
				$shot->items[] = [
					$this->encodeKey($k),
					is_string($k) && isset($this->keysToHide[strtolower($k)])
						? (object) ['key' => self::hideValue($v)]
						: $this->describeVar($v, $depth + 1),
					$visibility,
				];
			}
		}
		return (object) ['object' => $id];
	}


	/**
	 * @param  resource  $resource
	 */
	private function describeResource($resource, int $depth = 0): object
	{
		$id = 'r' . (int) $resource;
		$shot = &$this->snapshot[$id];
		if (!$shot) {
			$type = get_resource_type($resource);
			$shot = (object) ['name' => $type . ' resource'];
			if (isset($this->resourceExposers[$type])) {
				foreach (($this->resourceExposers[$type])($resource) as $k => $v) {
					$shot->items[] = [$k, $this->describeVar($v, $depth + 1)];
				}
			}
		}
		return (object) ['resource' => $id];
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


	private function exposeObject(object $obj): array
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


	private static function hideValue($var): string
	{
		return Dumper::HIDDEN_VALUE . ' (' . (is_object($var) ? Helpers::getClass($var) : gettype($var)) . ')';
	}
}
