<?php

/**
 * Test: Nette\Debug::dump() and locale.
 *
 * @author     David Grudl
 * @package    Nette
 * @subpackage UnitTests
 */

use Nette\Debug;



require __DIR__ . '/../bootstrap.php';



Debug::$consoleMode = TRUE;
Debug::$productionMode = FALSE;
setLocale(LC_ALL, 'czech');



Assert::match( 'array(2) [
   0 => -10.0
   1 => 10.3
]

', Debug::dump(array(-10.0, 10.3), TRUE));
