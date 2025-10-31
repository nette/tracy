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
		'x' => ['x', 'Test', Value::PropertyPublic],
		"\x00Test\x00y" => ['y', 'Test', Value::PropertyPrivate],
		"\x00*\x00z" => ['z', 'Test', Value::PropertyProtected],
	], Exposer::getProperties(Test::class));

	Assert::same([
		'x' => ['x', 'Child', Value::PropertyPublic],
		"\x00Child\x00y" => ['y', 'Child', Value::PropertyPrivate],
		"\x00*\x00z" => ['z', 'Child', Value::PropertyProtected],
		'x2' => ['x2', 'Child', Value::PropertyPublic],
		"\x00*\x00y2" => ['y2', 'Child', Value::PropertyProtected],
		"\x00Child\x00z2" => ['z2', 'Child', Value::PropertyPrivate],
		"\x00Test\x00y" => ['y', 'Test', Value::PropertyPrivate],
	], Exposer::getProperties(Child::class));

	Assert::same([
		'x' => ['x', 'Child', Value::PropertyPublic],
		"\x00Child\x00y" => ['y', 'Child', Value::PropertyPrivate],
		"\x00*\x00z" => ['z', 'Child', Value::PropertyProtected],
		'x2' => ['x2', 'Child', Value::PropertyPublic],
		"\x00*\x00y2" => ['y2', 'Child', Value::PropertyProtected],
		"\x00Child\x00z2" => ['z2', 'Child', Value::PropertyPrivate],
		"\x00Test\x00y" => ['y', 'Test', Value::PropertyPrivate],
	], Exposer::getProperties(GrandChild::class));
});


// exposeObject()

$value = new Value(Value::TypeObject);
Exposer::exposeObject(new Test, $value, new Describer);
Assert::equal([
	['x', [[0, 10], [1, null]], Value::PropertyPublic],
	['y', 'hello', 'Test'],
	['z', new Value(Value::TypeNumber, '30.0'), Value::PropertyProtected],
], $value->items);

$value = new Value(Value::TypeObject);
Exposer::exposeObject(new Child, $value, new Describer);
Assert::same([
	['x', 1, Value::PropertyPublic],
	['y', 2, 'Child'],
	['z', 3, Value::PropertyProtected],
	['x2', 4, Value::PropertyPublic],
	['y2', 5, Value::PropertyProtected],
	['z2', 6, 'Child'],
	['y', 'hello', 'Test'],
], $value->items);

$value = new Value(Value::TypeObject);
Exposer::exposeObject(new GrandChild, $value, new Describer);
Assert::same([
	['x', 1, Value::PropertyPublic],
	['y', 2, 'Child'],
	['z', 3, Value::PropertyProtected],
	['x2', 4, Value::PropertyPublic],
	['y2', 5, Value::PropertyProtected],
	['z2', 6, 'Child'],
	['y', 'hello', 'Test'],
], $value->items);
