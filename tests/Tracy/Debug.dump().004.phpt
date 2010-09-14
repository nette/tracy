<?php

/**
 * Test: Nette\Debug::dump() and $maxDepth and $maxLen.
 *
 * @author     David Grudl
 * @package    Nette
 * @subpackage UnitTests
 */

use Nette\Debug;



require __DIR__ . '/../initialize.php';



Debug::$consoleMode = TRUE;
Debug::$productionMode = FALSE;



$arr = array(
	'long' => str_repeat('Nette Framework', 1000),

	array(
		array(
			array('hello' => 'world'),
		),
	),

	'long2' => str_repeat('Nette Framework', 1000),

	(object) array(
		(object) array(
			(object) array('hello' => 'world'),
		),
	),
);

$arr[] = &$arr;
Assert::match( 'array(5) {
   "long" => "Nette FrameworkNette FrameworkNette FrameworkNette FrameworkNette FrameworkNette FrameworkNette FrameworkNette FrameworkNette FrameworkNette Framework ... " (15000)
   0 => array(1) [
      0 => array(1) [
         0 => array(1) { ... }
      ]
   ]
   "long2" => "Nette FrameworkNette FrameworkNette FrameworkNette FrameworkNette FrameworkNette FrameworkNette FrameworkNette FrameworkNette FrameworkNette Framework ... " (15000)
   1 => stdClass(1) {
      "0" => stdClass(1) {
         "0" => stdClass(1) { ... }
      }
   }
   2 => array(5) {
      "long" => "Nette FrameworkNette FrameworkNette FrameworkNette FrameworkNette FrameworkNette FrameworkNette FrameworkNette FrameworkNette FrameworkNette Framework ... " (15000)
      0 => array(1) [
         0 => array(1) [ ... ]
      ]
      "long2" => "Nette FrameworkNette FrameworkNette FrameworkNette FrameworkNette FrameworkNette FrameworkNette FrameworkNette FrameworkNette FrameworkNette Framework ... " (15000)
      1 => stdClass(1) {
         "0" => stdClass(1) { ... }
      }
      2 => array(6) { *RECURSION* }
   }
}

', Debug::dump($arr, TRUE) );



Debug::$maxDepth = 2;
Debug::$maxLen = 50;
Assert::match( 'array(5) {
   "long" => "Nette FrameworkNette FrameworkNette FrameworkNette ... " (15000)
   0 => array(1) [
      0 => array(1) [ ... ]
   ]
   "long2" => "Nette FrameworkNette FrameworkNette FrameworkNette ... " (15000)
   1 => stdClass(1) {
      "0" => stdClass(1) { ... }
   }
   2 => array(5) {
      "long" => "Nette FrameworkNette FrameworkNette FrameworkNette ... " (15000)
      0 => array(1) [ ... ]
      "long2" => "Nette FrameworkNette FrameworkNette FrameworkNette ... " (15000)
      1 => stdClass(1) { ... }
      2 => array(6) { *RECURSION* }
   }
}

', Debug::dump($arr, TRUE) );
