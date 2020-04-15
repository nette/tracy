<?php

/**
 * Test: Tracy\Dumper::toTerminal()
 */

declare(strict_types=1);

use Tester\Assert;
use Tracy\Dumper;


require __DIR__ . '/../bootstrap.php';
require __DIR__ . '/fixtures/DumpClass.php';


Assert::match("\x1b[1;33mnull\x1b[0m", Dumper::toTerminal(null));


Assert::match("\x1b[1;31marray\x1b[0m (4)\x1b[0m
\x1b[1;30m   \x1b[0m\x1b[1;37m0\x1b[0m => \x1b[1;32m1\x1b[0m
\x1b[1;30m   \x1b[0m\x1b[1;37m1\x1b[0m => \x1b[1;36m\"hello\"\x1b[0m (5)
\x1b[1;30m   \x1b[0m\x1b[1;37m2\x1b[0m => \x1b[1;31marray\x1b[0m ()
\x1b[1;30m   \x1b[0m\x1b[1;37m3\x1b[0m => \x1b[1;31marray\x1b[0m (2)\x1b[0m
\x1b[1;30m   |  \x1b[0m\x1b[1;37m0\x1b[0m => \x1b[1;33mtrue\x1b[0m
\x1b[1;30m   |  \x1b[0m\x1b[1;37m1\x1b[0m => \x1b[1;33mnull\x1b[0m
", Dumper::toTerminal([1, 'hello', [], [true, null]]));


$obj = new Child;
$obj->new = 7;
$obj->{0} = 8;
$obj->{1} = 9;
$obj->{''} = 10;

Assert::match("\x1b[1;31mChild\x1b[0m \x1b[0m#%d%\x1b[0m\x1b[0m
\x1b[1;30m   \x1b[0m\x1b[1;37mx\x1b[0m => \x1b[1;32m1\x1b[0m
\x1b[1;30m   \x1b[0m\x1b[1;37my\x1b[0m \x1b[1;30mprivate\x1b[0m => \x1b[1;32m2\x1b[0m
\x1b[1;30m   \x1b[0m\x1b[1;37mz\x1b[0m \x1b[1;30mprotected\x1b[0m => \x1b[1;32m3\x1b[0m
\x1b[1;30m   \x1b[0m\x1b[1;37mx2\x1b[0m => \x1b[1;32m4\x1b[0m
\x1b[1;30m   \x1b[0m\x1b[1;37my2\x1b[0m \x1b[1;30mprotected\x1b[0m => \x1b[1;32m5\x1b[0m
\x1b[1;30m   \x1b[0m\x1b[1;37mz2\x1b[0m \x1b[1;30mprivate\x1b[0m => \x1b[1;32m6\x1b[0m
\x1b[1;30m   \x1b[0m\x1b[1;37my\x1b[0m \x1b[1;30mprivate\x1b[0m => \x1b[1;36m\"hello\"\x1b[0m (5)
\x1b[1;30m   \x1b[0m\x1b[1;37mnew\x1b[0m => \x1b[1;32m7\x1b[0m
\x1b[1;30m   \x1b[0m\x1b[1;37m0\x1b[0m => \x1b[1;32m8\x1b[0m
\x1b[1;30m   \x1b[0m\x1b[1;37m1\x1b[0m => \x1b[1;32m9\x1b[0m
\x1b[1;30m   \x1b[0m\x1b[1;37m\"\"\x1b[0m => \x1b[1;32m10\x1b[0m
", Dumper::toTerminal($obj));


$arr = (object) ['x' => 1, 'y' => 2];
$arr->z = &$arr;
Assert::match("\x1b[1;31mstdClass\x1b[0m \x1b[0m#%d%\x1b[0m\x1b[0m
\x1b[1;30m   \x1b[0m\x1b[1;37mx\x1b[0m => \x1b[1;32m1\x1b[0m
\x1b[1;30m   \x1b[0m\x1b[1;37my\x1b[0m => \x1b[1;32m2\x1b[0m
\x1b[1;30m   \x1b[0m\x1b[1;37mz\x1b[0m => \x1b[0m&%d%\x1b[0m \x1b[1;31mstdClass\x1b[0m \x1b[0m#7\x1b[0m { RECURSION }
", Dumper::toTerminal($arr));
