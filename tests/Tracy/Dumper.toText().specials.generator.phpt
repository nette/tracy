<?php

/**
 * Test: Tracy\Dumper::toText() Generator
 */

declare(strict_types=1);

use Tester\Assert;
use Tracy\Dumper;

require __DIR__ . '/../bootstrap.php';


function gen()
{
	yield 1;
}


$gen = gen();

Assert::match(
	<<<'XX'
		Generator #%d%
		   file: '%a%:%d%'
		   this: null
		XX,
	Dumper::toText($gen),
);

$gen->next();
Assert::match('Generator (terminated) #%d%', Dumper::toText($gen));


// exposer must not start the generator
function gen2()
{
	throw new Exception('It must not occur');
	yield;
}


$gen = gen2();
Assert::match(
	<<<'XX'
		Generator #%d%
		   file: '%a%:%d%'
		   this: null
		XX,
	Dumper::toText($gen),
);
