<?php

/**
 * Test: Tracy\Dumper::toTerminal()
 */

declare(strict_types=1);

use Tester\Assert;
use Tracy\Dumper;


require __DIR__ . '/../bootstrap.php';


Assert::match("\x1b[1;33mnull\x1b[0m", Dumper::toTerminal(null));


Assert::match("\x1b[1;31marray\x1b[0m (4)\x1b[0m
\x1b[1;30m   \x1b[0m\x1b[1;37m0\x1b[0m => \x1b[1;32m1\x1b[0m
\x1b[1;30m   \x1b[0m\x1b[1;37m1\x1b[0m => \x1b[1;36m\"hello\"\x1b[0m (5)
\x1b[1;30m   \x1b[0m\x1b[1;37m2\x1b[0m => \x1b[1;31marray\x1b[0m ()
\x1b[1;30m   \x1b[0m\x1b[1;37m3\x1b[0m => \x1b[1;31marray\x1b[0m (2)\x1b[0m
\x1b[1;30m   |  \x1b[0m\x1b[1;37m0\x1b[0m => \x1b[1;33mtrue\x1b[0m
\x1b[1;30m   |  \x1b[0m\x1b[1;37m1\x1b[0m => \x1b[1;33mnull\x1b[0m
", Dumper::toTerminal([1, 'hello', [], [true, null]]));
