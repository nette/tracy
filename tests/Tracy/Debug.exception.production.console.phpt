<?php

/**
 * Test: Nette\Debug exception in production & console mode.
 *
 * @author     David Grudl
 * @category   Nette
 * @package    Nette
 * @subpackage UnitTests
 */

use Nette\Debug;



require __DIR__ . '/../initialize.php';



Debug::$consoleMode = TRUE;
Debug::$productionMode = TRUE;

Debug::enable();

throw new Exception('The my exception', 123);



__halt_compiler() ?>

------EXPECT------
ERROR:%A%
