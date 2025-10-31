<?php

/**
 * This file is part of the Tracy (https://tracy.nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Tracy\Dumper;

use Tracy;
use Tracy\Dumper\Nodes\ArrayNode;
use Tracy\Dumper\Nodes\NumberNode;
use Tracy\Dumper\Nodes\ObjectNode;
use Tracy\Dumper\Nodes\ReferenceNode;
use Tracy\Dumper\Nodes\ResourceNode;
use Tracy\Dumper\Nodes\StringNode;
use Tracy\Dumper\Nodes\TextNode;
use Tracy\Helpers;
use function array_map, array_slice, class_exists, count, explode, file, get_debug_type, get_resource_type, gettype, htmlspecialchars, implode, is_bool, is_file, is_finite, is_int, is_resource, is_string, is_subclass_of, json_encode, method_exists, preg_match, spl_object_id, str_replace, strlen, strpos, strtolower, trim, uksort;


/**
 * Converts PHP values to internal representation.
 * @internal
 */
final class Describer
{
	public const HiddenValue = '*****';

	// Number.MAX_SAFE_INTEGER
	private const JsSafeInteger = 1 << 53 - 1;

	public int $maxDepth = 7;
	public int $maxLength = 150;
	public int $maxItems = 100;

	/** @var array<int|string, Node> */
	public array $snapshot = [];
	public bool $debugInfo = false;
	public array $keysToHide = [];

	/** @var (callable(string, mixed): bool)|null */
	public $scrubber;

	public bool $location = false;

	/** @var array<string, callable(resource): array> */
	public array $resourceExposers = [];

	/** @var array<string, callable(object, ObjectNode, self): ?array> */
	public array $objectExposers = [];

	/** @var array<string, array{bool, string[]}> */
	public array $enumProperties = [];

	/** @var (int|\stdClass)[] */
	public array $references = [];


	public function describe(mixed $var): \stdClass
	{
		uksort($this->objectExposers, fn($a, $b): int => $b === '' || (class_exists($a, false) && is_subclass_of($a, $b)) ? -1 : 1);

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


	private function describeVar(mixed $var, int $depth = 0, ?int $refId = null): mixed
	{
		if ($var === null || is_bool($var)) {
			return $var;
		}

		$m = 'describe' . explode(' ', gettype($var))[0];
		return $this->$m($var, $depth, $refId);
	}


	private function describeInteger(int $num): NumberNode|int
	{
		return $num <= self::JsSafeInteger && $num >= -self::JsSafeInteger
			? $num
			: new NumberNode("$num");
	}


	private function describeDouble(float $num): NumberNode|float
	{
		if (is_nan($num)) {
			return new NumberNode('NAN');
		} elseif (is_infinite($num)) {
			return new NumberNode($num < 0 ? '-INF' : 'INF');
		}

		$js = json_encode($num);
		return strpos($js, '.')
			? $num
			: new NumberNode("$js.0"); // to distinct int and float in JS
	}


	private function describeString(string $s, int $depth = 0): StringNode|string
	{
		$encoded = Helpers::encodeString($s, $depth ? $this->maxLength : null);
		if ($encoded === $s) {
			return $encoded;
		} elseif (Helpers::isUtf8($s)) {
			return new StringNode($encoded, Helpers::utf8Length($s), binary: false);
		} else {
			return new StringNode($encoded, strlen($s), binary: true);
		}
	}


	private function describeArray(array $arr, int $depth = 0, ?int $refId = null): ArrayNode|ReferenceNode|array
	{
		if ($refId) {
			$res = new ReferenceNode('p' . $refId);
			$value = &$this->snapshot[$res->targetId];
			if ($value && $value->depth <= $depth) {
				return $res;
			}

			$value = new ArrayNode;
			$value->id = $res->targetId;
			$value->depth = $depth;
			if ($this->maxDepth && $depth >= $this->maxDepth) {
				$value->length = count($arr);
				return $res;
			} elseif ($depth && $this->maxItems && count($arr) > $this->maxItems) {
				$value->length = count($arr);
				$arr = array_slice($arr, 0, $this->maxItems, preserve_keys: true);
			}

			$items = &$value->items;

		} elseif ($arr && $this->maxDepth && $depth >= $this->maxDepth) {
			$res = new ArrayNode;
			$res->length = count($arr);
			return $res;

		} elseif ($depth && $this->maxItems && count($arr) > $this->maxItems) {
			$res = new ArrayNode;
			$res->length = count($arr);
			$res->depth = $depth;
			$items = &$res->items;
			$arr = array_slice($arr, 0, $this->maxItems, preserve_keys: true);
		}

		$items = [];
		foreach ($arr as $k => $v) {
			$refId = $this->getReferenceId($arr, $k);
			$items[] = [
				$this->describeVar($k, $depth + 1),
				$this->isSensitive((string) $k, $v)
					? new TextNode(self::hideValue($v))
					: $this->describeVar($v, $depth + 1, $refId),
			] + ($refId ? [2 => $refId] : []);
		}

		return $res ?? $items;
	}


	private function describeObject(object $obj, int $depth = 0): ObjectNode|ReferenceNode
	{
		$id = spl_object_id($obj);
		$value = &$this->snapshot[$id];
		if ($value && $value->depth <= $depth) {
			return new ReferenceNode($id);
		}

		$value = new ObjectNode(get_debug_type($obj));
		$value->id = $id;
		$value->depth = $depth;
		$value->holder = $obj; // to be not released by garbage collector in collecting mode

		if ($this->location) {
			$rc = $obj instanceof \Closure
				? new \ReflectionFunction($obj)
				: new \ReflectionClass($obj);
			if ($rc->getFileName() && ($editor = Helpers::editorUri($rc->getFileName(), $rc->getStartLine()))) {
				$value->editor = (object) ['file' => $rc->getFileName(), 'line' => $rc->getStartLine(), 'url' => $editor];
			}
		}

		if ($this->maxDepth && $depth < $this->maxDepth) {
			$value->items = [];
			$props = $this->exposeObject($obj, $value);
			foreach ($props ?? [] as $k => $v) {
				$this->addPropertyTo($value, (string) $k, $v, ObjectNode::PropertyVirtual, $this->getReferenceId($props, $k));
			}
		}

		return new ReferenceNode($id);
	}


	/**
	 * @param  resource  $resource
	 */
	private function describeResource($resource, int $depth = 0): ResourceNode|ReferenceNode
	{
		$id = 'r' . (int) $resource;
		$value = &$this->snapshot[$id];
		if (!$value) {
			$type = is_resource($resource) ? get_resource_type($resource) : 'closed';
			$value = new ResourceNode($type . ' resource', $id);
			$value->depth = $depth;
			if (isset($this->resourceExposers[$type])) {
				foreach (($this->resourceExposers[$type])($resource) as $k => $v) {
					$value->items[] = [htmlspecialchars($k), $this->describeVar($v, $depth + 1)];
				}
			}
		}

		return new ReferenceNode($id);
	}


	public function describeKey(string $key): StringNode|string
	{
		if (preg_match('#^[\w!\#$%&*+./;<>?@^{|}~-]{1,50}$#D', $key) && !preg_match('#^(true|false|null)$#iD', $key)) {
			return $key;
		}

		$value = $this->describeString($key);
		return is_string($value) // ensure result is Value
			? new StringNode($key, Helpers::utf8Length($key), binary: false)
			: $value;
	}


	public function addPropertyTo(
		ObjectNode $value,
		string $k,
		mixed $v,
		int $type = ObjectNode::PropertyVirtual,
		?int $refId = null,
		?string $class = null,
		?Node $described = null,
	): void
	{
		if ($value->depth && $this->maxItems && count($value->items ?? []) >= $this->maxItems) {
			$value->length = ($value->length ?? count($value->items)) + 1;
			return;
		}

		$class ??= $value instanceof ObjectNode ? $value->className : null;
		$value->items[] = [
			$this->describeKey($k),
			$type !== ObjectNode::PropertyVirtual && $this->isSensitive($k, $v, $class)
				? new TextNode(self::hideValue($v))
				: ($described ?? $this->describeVar($v, $value->depth + 1, $refId)),
			$type === ObjectNode::PropertyPrivate ? $class : $type,
		] + ($refId ? [3 => $refId] : []);
	}


	private function exposeObject(object $obj, ObjectNode $value): ?array
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


	private function isSensitive(string $key, mixed $val, ?string $class = null): bool
	{
		return $val instanceof \SensitiveParameterValue
			|| ($this->scrubber !== null && ($this->scrubber)($key, $val, $class))
			|| isset($this->keysToHide[strtolower($key)])
			|| isset($this->keysToHide[strtolower($class . '::$' . $key)]);
	}


	private static function hideValue(mixed $val): string
	{
		if ($val instanceof \SensitiveParameterValue) {
			$val = $val->getValue();
		}

		return self::HiddenValue . ' (' . get_debug_type($val) . ')';
	}


	public function describeEnumProperty(string $class, string $property, mixed $value): ?TextNode
	{
		[$set, $constants] = $this->enumProperties["$class::$property"] ?? null;
		if (!is_int($value)
			|| !$constants
			|| !($constants = Helpers::decomposeFlags($value, $set, $constants))
		) {
			return null;
		}

		$constants = array_map(fn(string $const): string => str_replace("$class::", 'self::', $const), $constants);
		return new TextNode(implode(' | ', $constants) . " ($value)");
	}


	public function getReferenceId(array $arr, string|int $key): ?int
	{
		return ($rr = \ReflectionReference::fromArrayElement($arr, $key))
			? ($this->references[$rr->getId()] ??= count($this->references) + 1)
			: null;
	}


	/**
	 * Finds the location where dump was called. Returns [file, line, code]
	 */
	private static function findLocation(): ?array
	{
		foreach (debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS) as $item) {
			if (isset($item['class']) && ($item['class'] === self::class || $item['class'] === Tracy\Dumper::class)) {
				$location = $item;
				continue;
			} elseif (isset($item['function'])) {
				try {
					$reflection = isset($item['class'])
						? new \ReflectionMethod($item['class'], $item['function'])
						: new \ReflectionFunction($item['function']);
					if (
						$reflection->isInternal()
						|| preg_match('#\s@tracySkipLocation\s#', (string) $reflection->getDocComment())
					) {
						$location = $item;
						continue;
					}
				} catch (\ReflectionException) {
				}
			}

			break;
		}

		if (isset($location['file'], $location['line']) && @is_file($location['file'])) { // @ - may trigger error
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
