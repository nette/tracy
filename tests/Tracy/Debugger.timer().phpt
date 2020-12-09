<?php

/**
 * Test: Tracy\Debugger::timer()
 */

declare(strict_types=1);

use Tester\Assert;
use Tracy\Debugger;

require __DIR__ . '/../bootstrap.php';


Debugger::timer();

sleep(1);

Debugger::timer('foo');

sleep(1);

Assert::same(2.0, round(Debugger::timer(), 1));

Assert::same(1.0, round(Debugger::timer('foo'), 1));
