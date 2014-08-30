<?php

/**
 * Test: Tracy\Dumper custom object dumpers
 */

use Tracy\Dumper,
	Tester\Assert;


require __DIR__ . '/../bootstrap.php';


$obj = new stdClass;
Assert::match( 'stdClass #%a%', Dumper::toText($obj) );


$obj->a = 1;
Assert::match( 'stdClass #%a%
   a => 1
', Dumper::toText($obj) );


$dumpers = array(
	'stdClass' => function($var) {
		return array('x' => $var->a + 1);
	},
);
Assert::match( 'stdClass #%a%
   x => 2
', Dumper::toText($obj, array(Dumper::OBJECT_DUMPERS => $dumpers))
);
