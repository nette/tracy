<?php

/**
 * This file is part of the Tracy (https://tracy.nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Tracy\Dumper;


/**
 * Exposes internal PHP objects.
 * @internal
 */
final class Exposer
{
	public const
		PROP_PUBLIC = 0,
		PROP_PROTECTED = 1,
		PROP_DYNAMIC = 3,
		PROP_VIRTUAL = 4;
		// private is string


	public static function exposeObject(object $obj, Structure $struct, Describer $describer): void
	{
		$defaults = get_class_vars(get_class($obj));
		$arr = (array) $obj;
		$tmp = $arr; // PHP bug #79477
		foreach ($tmp as $k => $v) {
			$refId = $describer->getReferenceId($tmp, $k);
			$type = self::PROP_PUBLIC;
			if (isset($k[0]) && $k[0] === "\x00") {
				$info = explode("\00", $k);
				$k = end($info);
				$type = $info[1] === '*' ? self::PROP_PROTECTED : $info[1];
			} else {
				$type = array_key_exists($k, $defaults) ? self::PROP_PUBLIC : self::PROP_DYNAMIC;
				$k = (string) $k;
			}
			$describer->addProperty($struct, $k, $v, $type, $refId);
		}
	}


	public static function exposeClosure(\Closure $obj): array
	{
		$rc = new \ReflectionFunction($obj);
		$res = [];
		foreach ($rc->getParameters() as $param) {
			$res[] = '$' . $param->getName();
		}
		return [
			'file' => $rc->getFileName(),
			'line' => $rc->getStartLine(),
			'variables' => $rc->getStaticVariables(),
			'parameters' => implode(', ', $res),
		];
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


	public static function exposePhpIncompleteClass(\__PHP_Incomplete_Class $obj): array
	{
		$info = ['className' => null, 'private' => [], 'protected' => [], 'public' => []];
		foreach ((array) $obj as $name => $value) {
			if ($name === '__PHP_Incomplete_Class_Name') {
				$info['className'] = $value;
			} elseif (preg_match('#^\x0\*\x0(.+)$#D', $name, $m)) {
				$info['protected'][$m[1]] = $value;
			} elseif (preg_match('#^\x0(.+)\x0(.+)$#D', $name, $m)) {
				$info['private'][$m[1] . '::$' . $m[2]] = $value;
			} else {
				$info['public'][$name] = $value;
			}
		}
		return $info;
	}
}
