<?php

/**
 * Test: Nette\Debug exception in non-HTML mode.
 *
 * @author     David Grudl
 * @category   Nette
 * @package    Nette
 * @subpackage UnitTests
 */

use Nette\Debug;



require __DIR__ . '/../initialize.php';



Debug::$consoleMode = FALSE;
Debug::$productionMode = FALSE;
header('Content-Type: text/plain');

Debug::enable();

throw new Exception('The my exception', 123);



__halt_compiler() ?>

------EXPECT------
