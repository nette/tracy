<?php

/**
 * Test: Tracy\Exposer::exposeObject()
 */

declare(strict_types=1);

use Tester\Assert;
use Tracy\Dumper\Describer;
use Tracy\Dumper\Exposer;
use Tracy\Dumper\Nodes\NumberNode;
use Tracy\Dumper\Nodes\ObjectNode;

require __DIR__ . '/../bootstrap.php';
require __DIR__ . '/fixtures/DumpClass.php';


// getProperties()

Assert::with(Exposer::class, function () {
	Assert::same([
		'x' => ['x', 'Test', ObjectNode::PropertyPublic],
		"\x00Test\x00y" => ['y', 'Test', ObjectNode::PropertyPrivate],
		"\x00*\x00z" => ['z', 'Test', ObjectNode::PropertyProtected],
	], Exposer::getProperties(Test::class));

	Assert::same([
		'x' => ['x', 'Child', ObjectNode::PropertyPublic],
		"\x00Child\x00y" => ['y', 'Child', ObjectNode::PropertyPrivate],
		"\x00*\x00z" => ['z', 'Child', ObjectNode::PropertyProtected],
		'x2' => ['x2', 'Child', ObjectNode::PropertyPublic],
		"\x00*\x00y2" => ['y2', 'Child', ObjectNode::PropertyProtected],
		"\x00Child\x00z2" => ['z2', 'Child', ObjectNode::PropertyPrivate],
		"\x00Test\x00y" => ['y', 'Test', ObjectNode::PropertyPrivate],
	], Exposer::getProperties(Child::class));

	Assert::same([
		'x' => ['x', 'Child', ObjectNode::PropertyPublic],
		"\x00Child\x00y" => ['y', 'Child', ObjectNode::PropertyPrivate],
		"\x00*\x00z" => ['z', 'Child', ObjectNode::PropertyProtected],
		'x2' => ['x2', 'Child', ObjectNode::PropertyPublic],
		"\x00*\x00y2" => ['y2', 'Child', ObjectNode::PropertyProtected],
		"\x00Child\x00z2" => ['z2', 'Child', ObjectNode::PropertyPrivate],
		"\x00Test\x00y" => ['y', 'Test', ObjectNode::PropertyPrivate],
	], Exposer::getProperties(GrandChild::class));
});


// exposeObject()

$value = new ObjectNode;
Exposer::exposeObject(new Test, $value, new Describer);
Assert::equal([
	['x', [[0, 10], [1, null]], ObjectNode::PropertyPublic],
	['y', 'hello', 'Test'],
	['z', new NumberNode('30.0'), ObjectNode::PropertyProtected],
], $value->items);

$value = new ObjectNode;
Exposer::exposeObject(new Child, $value, new Describer);
Assert::same([
	['x', 1, ObjectNode::PropertyPublic],
	['y', 2, 'Child'],
	['z', 3, ObjectNode::PropertyProtected],
	['x2', 4, ObjectNode::PropertyPublic],
	['y2', 5, ObjectNode::PropertyProtected],
	['z2', 6, 'Child'],
	['y', 'hello', 'Test'],
], $value->items);

$value = new ObjectNode;
Exposer::exposeObject(new GrandChild, $value, new Describer);
Assert::same([
	['x', 1, ObjectNode::PropertyPublic],
	['y', 2, 'Child'],
	['z', 3, ObjectNode::PropertyProtected],
	['x2', 4, ObjectNode::PropertyPublic],
	['y2', 5, ObjectNode::PropertyProtected],
	['z2', 6, 'Child'],
	['y', 'hello', 'Test'],
], $value->items);
