<?php declare(strict_types=1);

/**
 * This file is part of the Tracy (https://tracy.nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

namespace Tracy\Dumper;

use Dom;
use Ds;
use Uri;
use function array_diff_key, array_key_exists, count, end, explode, get_debug_type, get_mangled_object_vars, implode, iterator_to_array, sort;
use const PHP_VERSION_ID;


/**
 * Exposes internal PHP objects.
 * @internal
 */
final class Exposer
{
	public static function exposeObject(object $obj, Value $value, Describer $describer): void
	{
		if (PHP_VERSION_ID >= 80400 && (new \ReflectionClass($obj))->isUninitializedLazyObject($obj)) {
			self::exposeLazyObject($obj, $describer, $value);
			return;
		}

		$values = get_mangled_object_vars($obj);
		$props = self::getProperties($obj::class);

		foreach (array_diff_key((array) $obj, $values) as $k => $v) {
			$describer->addPropertyTo($value, (string) $k, $v);
		}

		foreach (array_diff_key($values, $props) as $k => $v) {
			$describer->addPropertyTo(
				$value,
				(string) $k,
				$v,
				Value::PropertyDynamic,
				$describer->getReferenceId($values, $k),
			);
		}

		foreach ($props as $k => [$name, $class, $type]) {
			if (array_key_exists($k, $values)) {
				$describer->addPropertyTo(
					$value,
					$name,
					$values[$k],
					$type,
					$describer->getReferenceId($values, $k),
					$class,
					$describer->describeEnumProperty($class, $name, $values[$k]),
				);
			} else {
				$virtual = PHP_VERSION_ID >= 80400 && (new \ReflectionProperty($class, $name))->isVirtual();
				$describer->addPropertyTo(
					$value,
					$name,
					null,
					$type,
					class: $class,
					described: new Value(Value::TypeText, $virtual ? '{virtual}' : 'unset'),
				);
			}
		}
	}


	/**
	 * @param  class-string  $class
	 * @return array<string, array{string, class-string, int}>
	 */
	private static function getProperties(string $class): array
	{
		static $cache;
		if (isset($cache[$class])) {
			return $cache[$class];
		}

		$rc = new \ReflectionClass($class);
		$parentProps = $rc->getParentClass() ? self::getProperties($rc->getParentClass()->getName()) : [];
		$props = [];

		foreach ($rc->getProperties() as $prop) {
			$name = $prop->getName();
			if ($prop->isStatic() || $prop->getDeclaringClass()->getName() !== $class) {
				// nothing
			} elseif ($prop->isPrivate()) {
				$props["\x00" . $class . "\x00" . $name] = [$name, $class, Value::PropertyPrivate];
			} elseif ($prop->isProtected()) {
				$props["\x00*\x00" . $name] = [$name, $class, Value::PropertyProtected];
			} else {
				$props[$name] = [$name, $class, Value::PropertyPublic];
				unset($parentProps["\x00*\x00" . $name]);
			}
		}

		return $cache[$class] = $props + $parentProps;
	}


	public static function exposeClosure(\Closure $obj, Value $value, Describer $describer): void
	{
		$rc = new \ReflectionFunction($obj);
		if ($describer->location) {
			$describer->addPropertyTo($value, 'file', $rc->getFileName() . ':' . $rc->getStartLine());
		}

		$params = [];
		foreach ($rc->getParameters() as $param) {
			$params[] = '$' . $param->getName();
		}

		$value->value .= '(' . implode(', ', $params) . ')';

		if ($this_ = $rc->getClosureThis()) {
			$describer->addPropertyTo($value, 'this', null, described: new Value(Value::TypeText, get_debug_type($this_)));
		}

		$uses = [];
		$useValue = new Value(Value::TypeObject);
		$useValue->depth = $value->depth + 1;
		foreach ($rc->getStaticVariables() as $name => $v) {
			$uses[] = '$' . $name;
			$describer->addPropertyTo($useValue, '$' . $name, $v);
		}

		if ($uses) {
			$useValue->value = implode(', ', $uses);
			$useValue->collapsed = true;
			$describer->addPropertyTo($value, 'use', null, described: $useValue);
		}
	}


	public static function exposeEnum(\UnitEnum $enum, Value $value, Describer $describer): void
	{
		$value->value = $enum::class . '::' . $enum->name;
		if ($enum instanceof \BackedEnum) {
			$describer->addPropertyTo($value, 'value', $enum->value);
			$value->collapsed = true;
		}
	}


	public static function exposeArrayObject(\ArrayObject $obj, Value $value, Describer $describer): void
	{
		$flags = $obj->getFlags();
		$obj->setFlags(\ArrayObject::STD_PROP_LIST);
		self::exposeObject($obj, $value, $describer);
		$obj->setFlags($flags);
		$describer->addPropertyTo($value, 'storage', $obj->getArrayCopy(), Value::PropertyPrivate, null, \ArrayObject::class);
		$value->value .= ' (' . count($obj) . ')';
	}


	public static function exposeDOMNode(\DOMNode|Dom\Node $obj, Value $value, Describer $describer): void
	{
		$props = [];
		foreach ((new \ReflectionClass($obj))->getProperties(\ReflectionProperty::IS_PUBLIC) as $prop) {
			if (!$prop->isStatic()) {
				$props[] = $prop->getName();
			}
		}

		sort($props);
		foreach ($props as $p) {
			$describer->addPropertyTo($value, $p, @$obj->$p, Value::PropertyPublic); // @ some props may be deprecated
		}
	}


	public static function exposeDOMNodeList(
		\DOMNodeList|\DOMNamedNodeMap|Dom\NodeList|Dom\NamedNodeMap|Dom\TokenList|Dom\HTMLCollection $obj,
		Value $value,
		Describer $describer,
	): void
	{
		$describer->addPropertyTo($value, 'length', $obj->length, Value::PropertyPublic);
		$describer->addPropertyTo($value, 'items', iterator_to_array($obj));
	}


	public static function exposeGenerator(\Generator $gen, Value $value, Describer $describer): void
	{
		try {
			$r = new \ReflectionGenerator($gen);
			$describer->addPropertyTo($value, 'file', $r->getExecutingFile() . ':' . $r->getExecutingLine());
			$describer->addPropertyTo($value, 'this', $r->getThis());
		} catch (\ReflectionException) {
			$value->value = $gen::class . ' (terminated)';
		}
	}


	public static function exposeFiber(\Fiber $fiber, Value $value, Describer $describer): void
	{
		if ($fiber->isTerminated()) {
			$value->value = $fiber::class . ' (terminated)';
		} elseif (!$fiber->isStarted()) {
			$value->value = $fiber::class . ' (not started)';
		} else {
			$r = new \ReflectionFiber($fiber);
			$describer->addPropertyTo($value, 'file', $r->getExecutingFile() . ':' . $r->getExecutingLine());
			$describer->addPropertyTo($value, 'callable', $r->getCallable());
		}
	}


	/** @return array{path: string} */
	public static function exposeSplFileInfo(\SplFileInfo $obj): array
	{
		return ['path' => $obj->getPathname()];
	}


	/** @return array<string, mixed> */
	public static function exposeCurl(\CurlHandle $curl): array
	{
		return curl_getinfo($curl);
	}


	public static function exposeWeakReference(\WeakReference $ref, Value $value, Describer $describer): void
	{
		if ($obj = $ref->get()) {
			$describer->addPropertyTo($value, 'object', $obj);
		} else {
			$value->value .= ' (dead)';
		}
	}


	public static function exposeUri(Uri\Rfc3986\Uri|Uri\WhatWg\Url $uri, Value $value, Describer $describer): void
	{
		$describer->addPropertyTo($value, 'uri', $uri instanceof Uri\WhatWg\Url ? $uri->toAsciiString() : $uri->toRawString());
		foreach ($uri->__debugInfo() as $k => $v) {
			if ($v !== null && $v !== '') {
				$describer->addPropertyTo($value, $k, $v);
			}
		}
	}


	public static function exposeSplObjectStorage(\SplObjectStorage $obj, Value $value, Describer $describer): void
	{
		$value->value .= ' (' . count($obj) . ')';
		foreach (clone $obj as $v) {
			$pair = new Value(Value::TypeObject, '');
			$pair->depth = $value->depth + 1;
			$describer->addPropertyTo($pair, 'key', $v);
			$describer->addPropertyTo($pair, 'value', $obj[$v]);
			$describer->addPropertyTo($value, '', null, described: $pair);
			assert($value->items !== null);
			$value->items[count($value->items) - 1][0] = '';
		}
	}


	public static function exposeWeakMap(\WeakMap $obj, Value $value, Describer $describer): void
	{
		$value->value .= ' (' . count($obj) . ')';
		foreach ($obj as $k => $v) {
			$pair = new Value(Value::TypeObject, '');
			$pair->depth = $value->depth + 1;
			$describer->addPropertyTo($pair, 'key', $k);
			$describer->addPropertyTo($pair, 'value', $v);
			$describer->addPropertyTo($value, '', null, described: $pair);
			assert($value->items !== null);
			$value->items[count($value->items) - 1][0] = '';
		}
	}


	public static function exposePhpIncompleteClass(
		\__PHP_Incomplete_Class $obj,
		Value $value,
		Describer $describer,
	): void
	{
		$values = get_mangled_object_vars($obj);
		$class = $values['__PHP_Incomplete_Class_Name'];
		unset($values['__PHP_Incomplete_Class_Name']);
		foreach ($values as $k => $v) {
			$refId = $describer->getReferenceId($values, $k);
			if (isset($k[0]) && $k[0] === "\x00") {
				$info = explode("\00", $k);
				$k = end($info);
				$type = $info[1] === '*' ? Value::PropertyProtected : Value::PropertyPrivate;
				$decl = $type === Value::PropertyPrivate ? $info[1] : null;
			} else {
				$type = Value::PropertyPublic;
				$k = (string) $k;
				$decl = null;
			}

			$describer->addPropertyTo($value, $k, $v, $type, $refId, $decl);
		}

		$value->value = $class . ' (Incomplete Class)';
	}


	public static function exposeDsCollection(
		Ds\Collection|Ds\Seq|Ds\Set|Ds\Heap $obj,
		Value $value,
		Describer $describer,
	): void
	{
		foreach (clone $obj as $k => $v) {
			$describer->addPropertyTo($value, (string) $k, $v);
		}
	}


	public static function exposeDsMap(
		Ds\Map $obj,
		Value $value,
		Describer $describer,
	): void
	{
		$i = 0;
		foreach ($obj as $k => $v) {
			$describer->addPropertyTo($value, (string) $i++, new Ds\Pair($k, $v));
		}
	}


	private static function exposeLazyObject(object $obj, Describer $describer, Value $value): void
	{
		$rc = new \ReflectionClass($obj);
		foreach ($rc->getProperties() as $prop) {
			if ($prop->isStatic() || $prop->isLazy($obj) || !$prop->isInitialized($obj)) {
				continue;
			}

			$type = match (true) {
				$prop->isPrivate() => Value::PropertyPrivate,
				$prop->isProtected() => Value::PropertyProtected,
				default => Value::PropertyPublic,
			};
			$v = $prop->getValue($obj);
			$describer->addPropertyTo(
				$value,
				$prop->getName(),
				$v,
				$type,
				class: $prop->getDeclaringClass()->getName(),
				described: $describer->describeEnumProperty($prop->getDeclaringClass()->getName(), $prop->getName(), $v),
			);
		}

		$value->value .= ' (lazy)';
	}
}
