<?php

/**
 * Test: Nette\Debug E_ERROR in production & console mode.
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

missing_funcion();



__halt_compiler() ?>

------EXPECT------
ERROR:%A%
