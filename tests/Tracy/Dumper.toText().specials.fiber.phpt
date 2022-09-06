<?php

/**
 * Test: Tracy\Dumper::toText() Fiber
 * @phpVersion 8.1
 */

declare(strict_types=1);

use Tester\Assert;
use Tracy\Dumper;

require __DIR__ . '/../bootstrap.php';


$fiber = new Fiber(function (): void {
	Fiber::suspend('fiber');
});

Assert::match('Fiber (not started) #%d%', Dumper::toText($fiber));

$fiber->start();
Assert::match(<<<'XX'
Fiber #%d%
   file: '%a%:%d%'
   callable: Closure() #%d%
XX
	, Dumper::toText($fiber));

$fiber->resume();
Assert::match('Fiber (terminated) #%d%', Dumper::toText($fiber));
