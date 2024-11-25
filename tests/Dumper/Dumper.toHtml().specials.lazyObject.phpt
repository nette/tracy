<?php

/**
 * Test: Tracy\Dumper::toHtml() & lazy object
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
		<pre class="tracy-dump tracy-light"><span class="tracy-dump-object">LazyClass (lazy)</span> <span class="tracy-dump-hash">#%d%</span></pre>
		XX,
	Dumper::toHtml($ghost, [Dumper::DEPTH => 3]),
);

// preinitialized property
$rc->getProperty('id')->setRawValueWithoutLazyInitialization($ghost, 123);

Assert::match(
	<<<'XX'
		<pre class="tracy-dump tracy-light"
		><span class="tracy-toggle"><span class="tracy-dump-object">LazyClass (lazy)</span> <span class="tracy-dump-hash">#%d%</span></span>
		<div><span class="tracy-dump-indent">   </span><span class="tracy-dump-public">id</span>: <span class="tracy-dump-number">123</span>
		</div></pre>
		XX,
	Dumper::toHtml($ghost, [Dumper::DEPTH => 3]),
);

// proxy
$proxy = $rc->newLazyProxy(function () { return new LazyClass; });
Assert::match(
	<<<'XX'
		<pre class="tracy-dump tracy-light"><span class="tracy-dump-object">LazyClass (lazy)</span> <span class="tracy-dump-hash">#%d%</span></pre>
		XX,
	Dumper::toHtml($proxy, [Dumper::DEPTH => 3]),
);
