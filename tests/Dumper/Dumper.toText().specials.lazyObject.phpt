<?php

/**
 * Test: Tracy\Dumper::toText() & lazy object
 * @phpversion 8.4
 */

declare(strict_types=1);

use Tester\Assert;
use Tracy\Dumper;

require __DIR__ . '/../bootstrap.php';


class LazyClass
{
	public function __construct(
		public int $id,
		public string $title,
	) {
	}
}

$rc = new ReflectionClass(LazyClass::class);
$ghost = $rc->newLazyGhost(function () {});

// new ghost
Assert::match(
	<<<'XX'
		LazyClass (lazy) #%d%
		XX,
	Dumper::toText($ghost, [Dumper::DEPTH => 3]),
);

// preinitialized property
$rc->getProperty('id')->setRawValueWithoutLazyInitialization($ghost, 123);

Assert::match(
	<<<'XX'
		LazyClass (lazy) #%d%
		   id: 123
		XX,
	Dumper::toText($ghost, [Dumper::DEPTH => 3]),
);

// proxy
$proxy = $rc->newLazyProxy(fn() => new LazyClass);
Assert::match(
	<<<'XX'
		LazyClass (lazy) #%d%
		XX,
	Dumper::toText($proxy, [Dumper::DEPTH => 3]),
);
