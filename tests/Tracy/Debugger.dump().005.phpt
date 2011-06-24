<?php

/**
 * Test: Nette\Diagnostics\Debugger::dump() and recursive arrays.
 *
 * @author     David Grudl
 * @package    Nette\Diagnostics
 * @subpackage UnitTests
 */

use Nette\Diagnostics\Debugger;



require __DIR__ . '/../bootstrap.php';



Debugger::$consoleMode = TRUE;
Debugger::$productionMode = FALSE;


$arr = array(1, 2, 3);
$arr[] = & $arr;
Assert::match( 'array(4) [
   0 => 1
   1 => 2
   2 => 3
   3 => array(4) [
      0 => 1
      1 => 2
      2 => 3
      3 => array(5) [ *RECURSION* ]
   ]
]
', Debugger::dump($arr, TRUE) );



$arr = array('x' => 1, 'y' => 2);
$arr[] = & $arr;
Assert::match( 'array(3) {
   x => 1
   y => 2
   0 => array(3) {
      x => 1
      y => 2
      0 => array(4) { *RECURSION* }
   }
}
', Debugger::dump($arr, TRUE) );
