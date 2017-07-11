<?php

/**
 * Test: Tracy\Debugger autoloading.
 * @outputMatch %A%: Declaration of B::test(%a?%) should be compatible %a% A::test() in %A%
 */

use Tracy\Debugger;


require __DIR__ . '/../bootstrap.php';


Debugger::$productionMode = false;
header('Content-Type: text/plain');

Debugger::enable();


// in this case autoloading is not triggered
include 'E_STRICT.php';
