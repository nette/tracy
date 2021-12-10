<?php

/**
 * Test: Tracy\Debugger autoloading.
 * @outputMatch %A%: Declaration of B::test(%a?%) should be compatible %a% A::test() in %A%
 * @phpVersion < 8
 */

declare(strict_types=1);

use Tracy\Debugger;

require __DIR__ . '/../bootstrap.php';


Debugger::$productionMode = false;
Debugger::enable();


// in this case autoloading is not triggered
include __DIR__ . '/fixtures/FATAL.php';
