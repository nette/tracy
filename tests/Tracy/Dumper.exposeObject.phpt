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
		'x' => ['x', 'Test', Value::PropPublic],
		"\x00Test\x00y" => ['y', 'Test', Value::PropPrivate],
		"\x00*\x00z" => ['z', 'Test', Value::PropProtected],
	], Exposer::getProperties(Test::class));

	Assert::same([
		'x' => ['x', 'Child', Value::PropPublic],
		"\x00Child\x00y" => ['y', 'Child', Value::PropPrivate],
		"\x00*\x00z" => ['z', 'Child', Value::PropProtected],
		'x2' => ['x2', 'Child', Value::PropPublic],
		"\x00*\x00y2" => ['y2', 'Child', Value::PropProtected],
		"\x00Child\x00z2" => ['z2', 'Child', Value::PropPrivate],
		"\x00Test\x00y" => ['y', 'Test', Value::PropPrivate],
	], Exposer::getProperties(Child::class));

	Assert::same([
		'x' => ['x', 'Child', Value::PropPublic],
		"\x00Child\x00y" => ['y', 'Child', Value::PropPrivate],
		"\x00*\x00z" => ['z', 'Child', Value::PropProtected],
		'x2' => ['x2', 'Child', Value::PropPublic],
		"\x00*\x00y2" => ['y2', 'Child', Value::PropProtected],
		"\x00Child\x00z2" => ['z2', 'Child', Value::PropPrivate],
		"\x00Test\x00y" => ['y', 'Test', Value::PropPrivate],
	], Exposer::getProperties(GrandChild::class));
});


// exposeObject()

$value = new Value(Value::TypeObject);
Exposer::exposeObject(new Test, $value, new Describer);
Assert::equal([
	['x', [[0, 10], [1, null]], Value::PropPublic],
	['y', 'hello', 'Test'],
	['z', new Value(Value::TypeNumber, '30.0'), Value::PropProtected],
], $value->items);

$value = new Value(Value::TypeObject);
Exposer::exposeObject(new Child, $value, new Describer);
Assert::same([
	['x', 1, Value::PropPublic],
	['y', 2, 'Child'],
	['z', 3, Value::PropProtected],
	['x2', 4, Value::PropPublic],
	['y2', 5, Value::PropProtected],
	['z2', 6, 'Child'],
	['y', 'hello', 'Test'],
], $value->items);

$value = new Value(Value::TypeObject);
Exposer::exposeObject(new GrandChild, $value, new Describer);
Assert::same([
	['x', 1, Value::PropPublic],
	['y', 2, 'Child'],
	['z', 3, Value::PropProtected],
	['x2', 4, Value::PropPublic],
	['y2', 5, Value::PropProtected],
	['z2', 6, 'Child'],
	['y', 'hello', 'Test'],
], $value->items);
