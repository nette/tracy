<?php declare(strict_types=1);

/**
 * Test: Tracy\Dumper::toText() & lazy object with virtual properties
 * @phpversion 8.4
 */

use Tester\Assert;
use Tracy\Dumper;

require __DIR__ . '/../bootstrap.php';


class LazyClassWithVirtualProp
{
	public function __construct(
		public int $id,
		public string $title,
	) {
	}

	public string $virtual {
		get => $this->title . '!';
	}
}

$rc = new ReflectionClass(LazyClassWithVirtualProp::class);
$ghost = $rc->newLazyGhost(function () {});

// new ghost - virtual property should not appear (it has no backing value)
Assert::match(
	<<<'XX'
		LazyClassWithVirtualProp (lazy) #%d%
		XX,
	Dumper::toText($ghost, [Dumper::DEPTH => 3]),
);

// preinitialized property - virtual property should still not appear
$rc->getProperty('id')->setRawValueWithoutLazyInitialization($ghost, 123);

Assert::match(
	<<<'XX'
		LazyClassWithVirtualProp (lazy) #%d%
		   id: 123
		XX,
	Dumper::toText($ghost, [Dumper::DEPTH => 3]),
);
