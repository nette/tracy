<?php

/**
 * Test: Nette\Debug notices and warnings in production mode.
 *
 * @author     David Grudl
 * @category   Nette
 * @package    Nette
 * @subpackage UnitTests
 */

use Nette\Debug;



require __DIR__ . '/../initialize.php';



Debug::$consoleMode = FALSE;
Debug::$productionMode = TRUE;

Debug::enable();

$x++;
rename('..', '..');



__halt_compiler() ?>

------EXPECT------
