<?php

/**
 * Test: Tracy\Exposer::exposeObject()
 */

declare(strict_types=1);

use Tester\Assert;
use Tracy\Dumper\Describer;
use Tracy\Dumper\Exposer;
use Tracy\Dumper\Nodes\ArrayNode;
use Tracy\Dumper\Nodes\CollectionItem;
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
$arr = new ArrayNode;
$arr->items = [new CollectionItem(0, 10), new CollectionItem(1, null)];
Assert::equal([
	new CollectionItem('x', $arr, type: ObjectNode::PropertyPublic),
	new CollectionItem('y', 'hello', type: 'Test'),
	new CollectionItem('z', new NumberNode('30.0'), type: ObjectNode::PropertyProtected),
], $value->items);

$value = new ObjectNode;
Exposer::exposeObject(new Child, $value, new Describer);
Assert::equal([
	new CollectionItem('x', 1, type: ObjectNode::PropertyPublic),
	new CollectionItem('y', 2, type: 'Child'),
	new CollectionItem('z', 3, type: ObjectNode::PropertyProtected),
	new CollectionItem('x2', 4, type: ObjectNode::PropertyPublic),
	new CollectionItem('y2', 5, type: ObjectNode::PropertyProtected),
	new CollectionItem('z2', 6, type: 'Child'),
	new CollectionItem('y', 'hello', type: 'Test'),
], $value->items);

$value = new ObjectNode;
Exposer::exposeObject(new GrandChild, $value, new Describer);
Assert::equal([
	new CollectionItem('x', 1, type: ObjectNode::PropertyPublic),
	new CollectionItem('y', 2, type: 'Child'),
	new CollectionItem('z', 3, type: ObjectNode::PropertyProtected),
	new CollectionItem('x2', 4, type: ObjectNode::PropertyPublic),
	new CollectionItem('y2', 5, type: ObjectNode::PropertyProtected),
	new CollectionItem('z2', 6, type: 'Child'),
	new CollectionItem('y', 'hello', type: 'Test'),
], $value->items);
