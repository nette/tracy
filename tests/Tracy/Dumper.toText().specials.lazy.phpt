<?php

/**
 * Test: Tracy\Dumper::toText() & lazy object
 * @phpversion 8.4
 */

declare(strict_types=1);

use Tester\Assert;
use Tracy\Dumper;

require __DIR__ . '/../bootstrap.php';


class MyClass
{
	public $foo;
}
 
$rc = new ReflectionClass(MyClass::class);
$ghost = $rc->newLazyGhost(function (MyClass $ghost) {});


Assert::match(
	<<<'XX'
		array (1)
		   0 => MyClass (lazy) #%d%
		   |  initializer: Closure($ghost) #%d%
		XX,
	Dumper::toText([$ghost], [Dumper::DEPTH => 3]),
);

