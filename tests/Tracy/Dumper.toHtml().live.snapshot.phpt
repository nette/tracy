<?php

/**
 * Test: Tracy\Dumper::toHtml() live
 */

use Tracy\Dumper,
	Tester\Assert;


require __DIR__ . '/../bootstrap.php';


$options = array(Dumper::LIVE => TRUE);


// auto-starting snapshot
$obj = new stdClass;
$obj->a = $obj;
Assert::match(
	'<pre class="tracy-dump" data-tracy-dump=\'{"object":1}\'></pre>',
	Dumper::toHtml($obj, $options)
);

$obj->a = 123;
Assert::match(
	'<pre class="tracy-dump" data-tracy-dump=\'{"object":2}\'></pre>',
	Dumper::toHtml($obj, $options)
);


// manual snapshot
$id = Dumper::startSnapshot();

Assert::match(
	'<pre class="tracy-dump" data-tracy-dump=\'{"object":3}\'></pre>',
	Dumper::toHtml($obj, $options)
);

$obj->a = 456;
Assert::match(
	'<pre class="tracy-dump" data-tracy-dump=\'{"object":3}\'></pre>',
	Dumper::toHtml($obj, $options)
);

Dumper::startSnapshot();
Assert::match(
	'<pre class="tracy-dump" data-tracy-dump=\'{"object":4}\'></pre>',
	Dumper::toHtml($obj, $options)
);


// clear manual snapshots
$data = Dumper::endSnapshot($id);
Assert::match('%h%', $hash = $data[3]['hash']);
Assert::same(
	array(
		3 => array('name' => 'stdClass', 'hash' => $hash, 'editor' => NULL, 'items' => array(array('a', 123, 0))),
		4 => array('name' => 'stdClass', 'hash' => $hash, 'editor' => NULL, 'items' => array(array('a', 456, 0))),
	),
	$data
);


// auto-starting snapshot
$obj->a = 789;
Assert::match(
	'<pre class="tracy-dump" data-tracy-dump=\'{"object":5}\'></pre>',
	Dumper::toHtml($obj, $options)
);

Assert::null(Dumper::endSnapshot(NULL, FALSE));

$data = Dumper::endSnapshot(NULL);
Assert::same(
	array(
		1 => array('name' => 'stdClass', 'hash' => $hash, 'editor' => NULL, 'items' => array(array('a', array('object' => 1), 0))),
		2 => array('name' => 'stdClass', 'hash' => $hash, 'editor' => NULL,	'items' => array(array('a', 123, 0))),
		5 => array('name' => 'stdClass', 'hash' => $hash, 'editor' => NULL, 'items' => array(array('a', 789, 0))),
	),
	$data
);
