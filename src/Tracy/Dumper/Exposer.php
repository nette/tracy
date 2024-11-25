<?php

/**
 * This file is part of the Tracy (https://tracy.nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Tracy\Dumper;

use Dom;
use Ds;
use Tracy\Dumper\Nodes\ObjectNode;
use Tracy\Dumper\Nodes\TextNode;
use function array_diff_key, array_key_exists, array_key_last, count, end, explode, get_mangled_object_vars, implode, iterator_to_array, preg_match_all, sort;


/**
 * Exposes internal PHP objects.
 * @internal
 */
final class Exposer
{
	public static function exposeObject(object $obj, ObjectNode $node, Describer $describer): void
	{
		if (PHP_VERSION_ID >= 80400 && (new \ReflectionClass($obj))->isUninitializedLazyObject($obj)) {
			self::exposeLazyObject($obj, $describer, $node);
			return;
		}

		$values = get_mangled_object_vars($obj);
		$props = self::getProperties($obj::class);

		foreach (array_diff_key((array) $obj, $values) as $k => $v) {
			$describer->addPropertyTo($node, (string) $k, $v);
		}

		foreach (array_diff_key($values, $props) as $k => $v) {
			$describer->addPropertyTo(
				$node,
				(string) $k,
				$v,
				ObjectNode::PropertyDynamic,
				$describer->getReferenceId($values, $k),
			);
		}

		foreach ($props as $k => [$name, $class, $type]) {
			if (array_key_exists($k, $values)) {
				$describer->addPropertyTo(
					$node,
					$name,
					$values[$k],
					$type,
					$describer->getReferenceId($values, $k),
					$class,
					$describer->describeEnumProperty($class, $name, $values[$k]),
				);
			} else {
				$describer->addPropertyTo(
					$node,
					$name,
					null,
					$type,
					class: $class,
					described: new TextNode('unset'),
				);
			}
		}
	}


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
				$props["\x00" . $class . "\x00" . $name] = [$name, $class, ObjectNode::PropertyPrivate];
			} elseif ($prop->isProtected()) {
				$props["\x00*\x00" . $name] = [$name, $class, ObjectNode::PropertyProtected];
			} else {
				$props[$name] = [$name, $class, ObjectNode::PropertyPublic];
				unset($parentProps["\x00*\x00" . $name]);
			}
		}

		return $cache[$class] = $props + $parentProps;
	}


	public static function exposeClosure(\Closure $obj, ObjectNode $node, Describer $describer): void
	{
		$rc = new \ReflectionFunction($obj);
		if ($describer->location) {
			$describer->addPropertyTo($node, 'file', $rc->getFileName() . ':' . $rc->getStartLine());
		}

		$params = [];
		foreach ($rc->getParameters() as $param) {
			$params[] = '$' . $param->getName();
		}

		$node->className .= '(' . implode(', ', $params) . ')';

		$uses = [];
		$useValue = new ObjectNode;
		$useValue->depth = $node->depth + 1;
		foreach ($rc->getStaticVariables() as $name => $v) {
			$uses[] = '$' . $name;
			$describer->addPropertyTo($useValue, '$' . $name, $v);
		}

		if ($uses) {
			$useValue->className = implode(', ', $uses);
			$useValue->collapsed = true;
			$describer->addPropertyTo($node, 'use', null, described: $useValue);
		}
	}


	public static function exposeEnum(\UnitEnum $enum, ObjectNode $node, Describer $describer): void
	{
		$node->className = $enum::class . '::' . $enum->name;
		if ($enum instanceof \BackedEnum) {
			$describer->addPropertyTo($node, 'value', $enum->value);
			$node->collapsed = true;
		}
	}


	public static function exposeArrayObject(\ArrayObject $obj, ObjectNode $node, Describer $describer): void
	{
		$flags = $obj->getFlags();
		$obj->setFlags(\ArrayObject::STD_PROP_LIST);
		self::exposeObject($obj, $node, $describer);
		$obj->setFlags($flags);
		$describer->addPropertyTo($node, 'storage', $obj->getArrayCopy(), ObjectNode::PropertyPrivate, null, \ArrayObject::class);
		$node->className .= ' (' . count($obj) . ')';
	}


	public static function exposeDOMNode(\DOMNode|Dom\Node $obj, ObjectNode $node, Describer $describer): void
	{
		$props = preg_match_all('#^\s*\[([^\]]+)\] =>#m', print_r($obj, return: true), $tmp) ? $tmp[1] : [];
		sort($props);
		foreach ($props as $p) {
			$describer->addPropertyTo($node, $p, @$obj->$p, ObjectNode::PropertyPublic); // @ some props may be deprecated
		}
	}


	public static function exposeDOMNodeList(
		\DOMNodeList|\DOMNamedNodeMap|Dom\NodeList|Dom\NamedNodeMap|Dom\TokenList|Dom\HTMLCollection $obj,
		ObjectNode $node,
		Describer $describer,
	): void
	{
		$describer->addPropertyTo($node, 'length', $obj->length, ObjectNode::PropertyPublic);
		$describer->addPropertyTo($node, 'items', iterator_to_array($obj));
	}


	public static function exposeGenerator(\Generator $gen, ObjectNode $node, Describer $describer): void
	{
		try {
			$r = new \ReflectionGenerator($gen);
			$describer->addPropertyTo($node, 'file', $r->getExecutingFile() . ':' . $r->getExecutingLine());
			$describer->addPropertyTo($node, 'this', $r->getThis());
		} catch (\ReflectionException) {
			$node->className = $gen::class . ' (terminated)';
		}
	}


	public static function exposeFiber(\Fiber $fiber, ObjectNode $node, Describer $describer): void
	{
		if ($fiber->isTerminated()) {
			$node->className = $fiber::class . ' (terminated)';
		} elseif (!$fiber->isStarted()) {
			$node->className = $fiber::class . ' (not started)';
		} else {
			$r = new \ReflectionFiber($fiber);
			$describer->addPropertyTo($node, 'file', $r->getExecutingFile() . ':' . $r->getExecutingLine());
			$describer->addPropertyTo($node, 'callable', $r->getCallable());
		}
	}


	public static function exposeSplFileInfo(\SplFileInfo $obj): array
	{
		return ['path' => $obj->getPathname()];
	}


	public static function exposeSplObjectStorage(\SplObjectStorage $obj, ObjectNode $node, Describer $describer): void
	{
		$node->className .= ' (' . count($obj) . ')';
		foreach (clone $obj as $v) {
			$pair = new ObjectNode;
			$pair->depth = $node->depth + 1;
			$describer->addPropertyTo($pair, 'key', $v);
			$describer->addPropertyTo($pair, 'value', $obj[$v]);
			$describer->addPropertyTo($node, '', null, described: $pair);
			$node->items[array_key_last($node->items)]->key = '';
		}
	}


	public static function exposeWeakMap(\WeakMap $obj, ObjectNode $node, Describer $describer): void
	{
		$node->className .= ' (' . count($obj) . ')';
		foreach ($obj as $k => $v) {
			$pair = new ObjectNode;
			$pair->depth = $node->depth + 1;
			$describer->addPropertyTo($pair, 'key', $k);
			$describer->addPropertyTo($pair, 'value', $v);
			$describer->addPropertyTo($node, '', null, described: $pair);
			$node->items[array_key_last($node->items)]->key = '';
		}
	}


	public static function exposePhpIncompleteClass(
		\__PHP_Incomplete_Class $obj,
		ObjectNode $node,
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
				$type = $info[1] === '*' ? ObjectNode::PropertyProtected : ObjectNode::PropertyPrivate;
				$decl = $type === ObjectNode::PropertyPrivate ? $info[1] : null;
			} else {
				$type = ObjectNode::PropertyPublic;
				$k = (string) $k;
				$decl = null;
			}

			$describer->addPropertyTo($node, $k, $v, $type, $refId, $decl);
		}

		$node->className = $class . ' (Incomplete Class)';
	}


	public static function exposeDsCollection(
		Ds\Collection $obj,
		ObjectNode $node,
		Describer $describer,
	): void
	{
		foreach (clone $obj as $k => $v) {
			$describer->addPropertyTo($node, (string) $k, $v);
		}
	}


	public static function exposeDsMap(
		Ds\Map $obj,
		ObjectNode $node,
		Describer $describer,
	): void
	{
		$i = 0;
		foreach ($obj as $k => $v) {
			$describer->addPropertyTo($node, (string) $i++, new Ds\Pair($k, $v));
		}
	}


	private static function exposeLazyObject(object $obj, Describer $describer, ObjectNode $node): void
	{
		$rc = new \ReflectionClass($obj);

		if ($initializer = $rc->getLazyInitializer($obj)) {
			//$describer->addPropertyTo($node, 'initializer', $initializer, ObjectNode::PropertyVirtual);
		}

		foreach ($rc->getProperties(\ReflectionProperty::IS_PUBLIC) as $prop) {
			if (!$prop->isLazy($obj)) {
				$describer->addPropertyTo(
					$node,
					$prop->getName(),
					$prop->getValue($obj),
					ObjectNode::PropertyPublic,
					described: $describer->describeEnumProperty($obj::class, $prop->getName(), $prop->getValue($obj)),
				);
			}
		}

		$node->className .= ' (lazy)';
	}
}
