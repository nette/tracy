<?php declare(strict_types=1);

/**
 * Test: Tracy\Dumper::toText() & lazy object with property hooks
 * @phpversion 8.4
 */

use Tester\Assert;
use Tracy\Dumper;

require __DIR__ . '/../bootstrap.php';


class LazyClassWithHooks
{
	public static array $log = [];

	public string $virtual {
		get {
			self::$log[] = 'virtual';
			return $this->title . '!';
		}
	}

	public string $backed = '' {
		get {
			self::$log[] = 'backed';
			return strtoupper($this->backed);
		}
	}


	public function __construct(
		public int $id,
		public string $title,
	) {
	}
}

$rc = new ReflectionClass(LazyClassWithHooks::class);
$ghost = $rc->newLazyGhost(function (LazyClassWithHooks $obj) {
	LazyClassWithHooks::$log[] = 'initializer';
	$obj->__construct(1, 'title');
});
$rc->getProperty('id')->setRawValueWithoutLazyInitialization($ghost, 123);
$rc->getProperty('backed')->setRawValueWithoutLazyInitialization($ghost, 'raw');

Assert::match(
	<<<'XX'
		LazyClassWithHooks (lazy) #%d%
		   virtual: {virtual}
		   backed: 'raw'
		   id: 123
		XX,
	Dumper::toText($ghost, [Dumper::DEPTH => 3]),
);

Assert::same([], LazyClassWithHooks::$log);
Assert::true($rc->isUninitializedLazyObject($ghost));
