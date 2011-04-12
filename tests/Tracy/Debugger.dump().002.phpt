<?php

/**
 * Test: Nette\Debug::dump() with $showLocation.
 *
 * @author     David Grudl
 * @package    Nette
 * @subpackage UnitTests
 */

use Nette\Debug;



require __DIR__ . '/../bootstrap.php';



Debug::$consoleMode = FALSE;
Debug::$productionMode = FALSE;



Debug::$showLocation = TRUE;

ob_start();
Debug::dump('xxx');
Assert::match( '<pre class="nette-dump">"xxx" (3) <small>in file %a% on line %d%</small>
</pre>', ob_get_clean() );
