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
		return $this->toJson($var);
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
				? (strpos($tmp = json_encode($var), '.') ? $var : (object) ['number' => "$tmp.0"])
				: (object) ['number' => (string) $var];

		} elseif (is_string($var)) {
			$s = Helpers::encodeString($var, $this->maxLength);
			if ($s === $var) {
				return $s;
			}
			return (object) ['string' => $s, 'length' => strlen($var)];

		} elseif (is_array($var)) {
			static $marker;
			if ($marker === null) {
				$marker = uniqid("\x00", true);
			}
			if (count($var) && (isset($var[$marker]) || $depth >= $this->maxDepth)) {
				return (object) ['stop' => [count($var) - isset($var[$marker]), isset($var[$marker])]];
			}
			$res = [];
			try {
				$var[$marker] = true;
				foreach ($var as $k => &$v) {
					if ($k !== $marker) {
						$res[] = [
							$this->encodeKey($k),
							is_string($k) && isset($this->keysToHide[strtolower($k)])
								? (object) ['key' => self::hideValue($v)]
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
			if ($obj && $obj->depth <= $depth) {
				return (object) ['object' => $id];
			}

			$obj = $obj ?: (object) [
				'name' => Helpers::getClass($var),
				'depth' => $depth,
				'object' => $var,
			];
			if (empty($obj->editor) && $this->location) {
				$rc = $var instanceof \Closure ? new \ReflectionFunction($var) : new \ReflectionClass($var);
				if ($editor = $rc->getFileName() ? Helpers::editorUri($rc->getFileName(), $rc->getStartLine()) : null) {
					$obj->editor = (object) ['file' => $rc->getFileName(), 'line' => $rc->getStartLine(), 'url' => $editor];
				}
			}

			if ($depth < $this->maxDepth || !$this->maxDepth) {
				$obj->depth = $depth;
				$obj->items = [];

				foreach ($this->exportObject($var) as $k => $v) {
					$vis = 0;
					if (isset($k[0]) && $k[0] === "\x00") {
						$vis = $k[1] === '*' ? 1 : 2;
						$k = substr($k, strrpos($k, "\x00") + 1);
					}
					$obj->items[] = [
						$this->encodeKey($k),
						is_string($k) && isset($this->keysToHide[strtolower($k)])
							? (object) ['key' => self::hideValue($v)]
							: $this->toJson($v, $depth + 1),
						$vis,
					];
				}
			}
			return (object) ['object' => $id];

		} elseif (is_resource($var)) {
			$id = 'r' . (int) $var;
			$obj = &$this->snapshot[$id];
			if (!$obj) {
				$type = get_resource_type($var);
				$obj = (object) ['name' => $type . ' resource'];
				if (isset($this->resourceExposers[$type])) {
					foreach (($this->resourceExposers[$type])($var) as $k => $v) {
						$obj->items[] = [$k, $this->toJson($v, $depth + 1)];
					}
				}
			}
			return (object) ['resource' => $id];

		} else {
			return (object) ['type' => 'unknown type'];
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


	private static function hideValue($var): string
	{
		return Dumper::HIDDEN_VALUE . ' (' . (is_object($var) ? Helpers::getClass($var) : gettype($var)) . ')';
	}
}
