<?php

/**
 * This file is part of the Tracy (https://tracy.nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Tracy\Dumper;
use Ds;


/**
 * Exposes internal PHP objects.
 * @internal
 */
final class Exposer
{
	public static function exposeObject(object $obj, Value $value, Describer $describer): void
	{
		$values = (array) $obj;
		$props = self::getProperties($obj::class);

		foreach (array_diff_key($values, $props) as $k => $v) {
			$describer->addPropertyTo(
				$value,
				(string) $k,
				$v,
				Value::PropDynamic,
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
				);
			} else {
				$value->items[] = [
					$name,
					new Value(Value::TypeText, 'unset'),
					$type === Value::PropPrivate ? $class : $type,
				];
			}
		}
	}


	private static function getProperties($class): array
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
				$props["\x00" . $class . "\x00" . $name] = [$name, $class, Value::PropPrivate];
			} elseif ($prop->isProtected()) {
				$props["\x00*\x00" . $name] = [$name, $class, Value::PropProtected];
			} else {
				$props[$name] = [$name, $class, Value::PropPublic];
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
			$value->items[] = ['use', $useValue];
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
		$describer->addPropertyTo($value, 'storage', $obj->getArrayCopy(), Value::PropPrivate, null, \ArrayObject::class);
	}


	public static function exposeDOMNode(\DOMNode $obj, Value $value, Describer $describer): void
	{
		$props = preg_match_all('#^\s*\[([^\]]+)\] =>#m', print_r($obj, true), $tmp) ? $tmp[1] : [];
		sort($props);
		foreach ($props as $p) {
			$describer->addPropertyTo($value, $p, $obj->$p, Value::PropPublic);
		}
	}


	/**
	 * @param  \DOMNodeList|\DOMNamedNodeMap  $obj
	 */
	public static function exposeDOMNodeList($obj, Value $value, Describer $describer): void
	{
		$describer->addPropertyTo($value, 'length', $obj->length, Value::PropPublic);
		$describer->addPropertyTo($value, 'items', iterator_to_array($obj));
	}


	public static function exposeGenerator(\Generator $gen, Value $value, Describer $describer): void
	{
		try {
			$r = new \ReflectionGenerator($gen);
			$describer->addPropertyTo($value, 'file', $r->getExecutingFile() . ':' . $r->getExecutingLine());
			$describer->addPropertyTo($value, 'this', $r->getThis());
		} catch (\ReflectionException $e) {
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


	public static function exposeSplFileInfo(\SplFileInfo $obj): array
	{
		return ['path' => $obj->getPathname()];
	}


	public static function exposeSplObjectStorage(\SplObjectStorage $obj): array
	{
		$res = [];
		foreach (clone $obj as $item) {
			$res[] = ['object' => $item, 'data' => $obj[$item]];
		}

		return $res;
	}


	public static function exposePhpIncompleteClass(
		\__PHP_Incomplete_Class $obj,
		Value $value,
		Describer $describer,
	): void {
		$values = (array) $obj;
		$class = $values['__PHP_Incomplete_Class_Name'];
		unset($values['__PHP_Incomplete_Class_Name']);
		foreach ($values as $k => $v) {
			$refId = $describer->getReferenceId($values, $k);
			if (isset($k[0]) && $k[0] === "\x00") {
				$info = explode("\00", $k);
				$k = end($info);
				$type = $info[1] === '*' ? Value::PropProtected : Value::PropPrivate;
				$decl = $type === Value::PropPrivate ? $info[1] : null;
			} else {
				$type = Value::PropPublic;
				$k = (string) $k;
				$decl = null;
			}

			$describer->addPropertyTo($value, $k, $v, $type, $refId, $decl);
		}

		$value->value = $class . ' (Incomplete Class)';
	}


	public static function exposeDsCollection(
		Ds\Collection $obj,
		Value $value,
		Describer $describer,
	): void {
		foreach ($obj as $k => $v) {
			$describer->addPropertyTo($value, (string) $k, $v, Value::PropVirtual);
		}
	}


	public static function exposeDsMap(
		Ds\Map $obj,
		Value $value,
		Describer $describer,
	): void {
		$i = 0;
		foreach ($obj as $k => $v) {
			$describer->addPropertyTo($value, (string) $i++, new Ds\Pair($k, $v), Value::PropVirtual);
		}
	}
}
