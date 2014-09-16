<?php

/**
 * Test: Tracy\Dumper custom object exporters
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


$exporters = array(
	'stdClass' => function($var) {
		return array('x' => $var->a + 1);
	},
);
Assert::match( 'stdClass #%a%
   x => 2
', Dumper::toText($obj, array(Dumper::OBJECT_EXPORTERS => $exporters))
);


$obj = unserialize('O:1:"Y":7:{s:1:"a";N;s:1:"b";i:2;s:4:"' . "\0" . '*' . "\0" . 'c";N;s:4:"' . "\0" . '*' . "\0" . 'd";s:1:"d";s:4:"' . "\0" . 'Y' . "\0" . 'e";N;s:4:"' . "\0" . 'Y' . "\0" . 'i";s:3:"bar";s:4:"' . "\0" . 'X' . "\0" . 'i";s:3:"foo";}');

Assert::match( '__PHP_Incomplete_Class #%a%
   className => "Y"
   private => array (3)
   |  "Y::$e" => NULL
   |  "Y::$i" => "bar" (3)
   |  "X::$i" => "foo" (3)
   protected => array (2)
   |  c => NULL
   |  d => "d"
   public => array (2)
   |  a => NULL
   |  b => 2', Dumper::toText($obj) );
