<?php

/**
 * Test: Tracy\Exposer::exposeObject()
 */

declare(strict_types=1);

use Tester\Assert;
use Tracy\Dumper\Describer;
use Tracy\Dumper\Exposer;
use Tracy\Dumper\Value;

require __DIR__ . '/../bootstrap.php';
require __DIR__ . '/fixtures/DumpClass.php';


// getProperties()

Assert::with(Exposer::class, function () {
	Assert::same([
		'x' => ['x', 'Test', Value::PROP_PUBLIC],
		"\x00Test\x00y" => ['y', 'Test', Value::PROP_PRIVATE],
		"\x00*\x00z" => ['z', 'Test', Value::PROP_PROTECTED],
	], Exposer::getProperties(Test::class));

	Assert::same([
		'x' => ['x', 'Child', Value::PROP_PUBLIC],
		"\x00Child\x00y" => ['y', 'Child', Value::PROP_PRIVATE],
		"\x00*\x00z" => ['z', 'Child', Value::PROP_PROTECTED],
		'x2' => ['x2', 'Child', Value::PROP_PUBLIC],
		"\x00*\x00y2" => ['y2', 'Child', Value::PROP_PROTECTED],
		"\x00Child\x00z2" => ['z2', 'Child', Value::PROP_PRIVATE],
		"\x00Test\x00y" => ['y', 'Test', Value::PROP_PRIVATE],
	], Exposer::getProperties(Child::class));

	Assert::same([
		'x' => ['x', 'Child', Value::PROP_PUBLIC],
		"\x00Child\x00y" => ['y', 'Child', Value::PROP_PRIVATE],
		"\x00*\x00z" => ['z', 'Child', Value::PROP_PROTECTED],
		'x2' => ['x2', 'Child', Value::PROP_PUBLIC],
		"\x00*\x00y2" => ['y2', 'Child', Value::PROP_PROTECTED],
		"\x00Child\x00z2" => ['z2', 'Child', Value::PROP_PRIVATE],
		"\x00Test\x00y" => ['y', 'Test', Value::PROP_PRIVATE],
	], Exposer::getProperties(GrandChild::class));
});


// exposeObject()

$value = new Value(Value::TYPE_OBJECT);
Exposer::exposeObject(new Test, $value, new Describer);
Assert::equal([
	['x', [[0, 10], [1, null]], Value::PROP_PUBLIC],
	['y', 'hello', 'Test'],
	['z', new Value(Value::TYPE_NUMBER, '30.0'), Value::PROP_PROTECTED],
], $value->items);

$value = new Value(Value::TYPE_OBJECT);
Exposer::exposeObject(new Child, $value, new Describer);
Assert::same([
	['x', 1, Value::PROP_PUBLIC],
	['y', 2, 'Child'],
	['z', 3, Value::PROP_PROTECTED],
	['x2', 4, Value::PROP_PUBLIC],
	['y2', 5, Value::PROP_PROTECTED],
	['z2', 6, 'Child'],
	['y', 'hello', 'Test'],
], $value->items);

$value = new Value(Value::TYPE_OBJECT);
Exposer::exposeObject(new GrandChild, $value, new Describer);
Assert::same([
	['x', 1, Value::PROP_PUBLIC],
	['y', 2, 'Child'],
	['z', 3, Value::PROP_PROTECTED],
	['x2', 4, Value::PROP_PUBLIC],
	['y2', 5, Value::PROP_PROTECTED],
	['z2', 6, 'Child'],
	['y', 'hello', 'Test'],
], $value->items);
