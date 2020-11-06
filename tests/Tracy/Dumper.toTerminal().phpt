<?php

/**
 * Test: Tracy\Dumper::toTerminal()
 */

declare(strict_types=1);

use Tester\Assert;
use Tracy\Dumper;


require __DIR__ . '/../bootstrap.php';
require __DIR__ . '/fixtures/DumpClass.php';


Assert::match("\e[1;33mnull\e[0m", Dumper::toTerminal(null));


Assert::match(<<<XX
\e[1;31marray\e[0m (4)\e[0m
\e[1;30m   \e[0m\e[1;37m0\e[0m => \e[1;32m1\e[0m
\e[1;30m   \e[0m\e[1;37m1\e[0m => \e[1;36m"hello"\e[0m (5)
\e[1;30m   \e[0m\e[1;37m2\e[0m => \e[1;31marray\e[0m ()
\e[1;30m   \e[0m\e[1;37m3\e[0m => \e[1;31marray\e[0m (2)\e[0m
\e[1;30m   |  \e[0m\e[1;37m0\e[0m => \e[1;33mtrue\e[0m
\e[1;30m   |  \e[0m\e[1;37m1\e[0m => \e[1;33mnull\e[0m
XX
, Dumper::toTerminal([1, 'hello', [], [true, null]]));


$obj = new Child;
$obj->new = 7;
$obj->{0} = 8;
$obj->{1} = 9;
$obj->{''} = 10;

Assert::match(<<<XX
\e[1;31mChild\e[0m \e[0m#%d%\e[0m\e[0m
\e[1;30m   \e[0m\e[1;37mx\e[0m => \e[1;32m1\e[0m
\e[1;30m   \e[0m\e[1;37my\e[0m \e[1;30mprivate\e[0m => \e[1;32m2\e[0m
\e[1;30m   \e[0m\e[1;37mz\e[0m \e[1;30mprotected\e[0m => \e[1;32m3\e[0m
\e[1;30m   \e[0m\e[1;37mx2\e[0m => \e[1;32m4\e[0m
\e[1;30m   \e[0m\e[1;37my2\e[0m \e[1;30mprotected\e[0m => \e[1;32m5\e[0m
\e[1;30m   \e[0m\e[1;37mz2\e[0m \e[1;30mprivate\e[0m => \e[1;32m6\e[0m
\e[1;30m   \e[0m\e[1;37my\e[0m \e[1;30mprivate\e[0m => \e[1;36m"hello"\e[0m (5)
\e[1;30m   \e[0m\e[1;37mnew\e[0m => \e[1;32m7\e[0m
\e[1;30m   \e[0m\e[1;37m0\e[0m => \e[1;32m8\e[0m
\e[1;30m   \e[0m\e[1;37m1\e[0m => \e[1;32m9\e[0m
\e[1;30m   \e[0m\e[1;37m""\e[0m => \e[1;32m10\e[0m
XX
, Dumper::toTerminal($obj));


$arr = (object) ['x' => 1, 'y' => 2];
$arr->z = &$arr;
Assert::match(<<<XX
\e[1;31mstdClass\e[0m \e[0m#%d%\e[0m\e[0m
\e[1;30m   \e[0m\e[1;37mx\e[0m => \e[1;32m1\e[0m
\e[1;30m   \e[0m\e[1;37my\e[0m => \e[1;32m2\e[0m
\e[1;30m   \e[0m\e[1;37mz\e[0m => \e[1;31mstdClass\e[0m \e[0m#%d%\e[0m { RECURSION }
XX
, Dumper::toTerminal($arr));
