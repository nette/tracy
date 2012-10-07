<?php

/**
 * Test: Nette\Diagnostics\Dump::toText() recursion
 *
 * @author     David Grudl
 * @package    Nette\Diagnostics
 */

use Nette\Diagnostics\Dump;



require __DIR__ . '/../bootstrap.php';



$arr = array(1, 2, 3);
$arr[] = & $arr;
Assert::match( 'array (4)
   0 => 1
   1 => 2
   2 => 3
   3 => array (4)
   |  0 => 1
   |  1 => 2
   |  2 => 3
   |  3 => array (4) [ RECURSION ]
', Dump::toText($arr) );



$arr = (object) array('x' => 1, 'y' => 2);
$arr->z = & $arr;
Assert::match( 'stdClass (3)
   x => 1
   y => 2
   z => stdClass (3) { RECURSION }
', Dump::toText($arr) );
