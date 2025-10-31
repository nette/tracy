<?php

/**
 * Test: Tracy\Dumper::toText() enum/flags property
 */

declare(strict_types=1);

use Tester\Assert;
use Tracy\Dumper;

require __DIR__ . '/../bootstrap.php';


class Flags
{
	public const Null = 0;
	public const A = 1 << 0;
	public const B = 1 << 1;
	public const C = 1 << 2;
	public const All = self::A | self::B | self::C;

	public int $flag;
	public int $flags;
}

class Child extends Flags
{
}


Dumper::addEnumProperty(Flags::class, 'flag', ['Flags::A', 'Flags::B', 'Flags::C', 'Flags::Null']);
Dumper::addEnumProperty(Flags::class, 'flags', ['Flags::All', 'Flags::A', 'Flags::B', 'Flags::C', 'Flags::Null'], set: true);

// valid
$child = new Child;
$child->flag = $child::B;
$child->flags = $child::A | $child::B;

Assert::match(
	<<<'XX'
		Child #%d%
		   flag: self::B (2)
		   flags: self::A | self::B (3)
		XX,
	Dumper::toText($child),
);


// all
$child = new Child;
$child->flags = $child::All;

Assert::match(
	<<<'XX'
		Child #%d%
		   flag: unset
		   flags: self::All (7)
		XX,
	Dumper::toText($child),
);


// null
$child = new Child;
$child->flag = 0;
$child->flags = 0;

Assert::match(
	<<<'XX'
		Child #%d%
		   flag: self::Null (0)
		   flags: 0
		XX,
	Dumper::toText($child),
);


// invalid
$child = new Child;
$child->flag = 16;
$child->flags = 16;

Assert::match(
	<<<'XX'
		Child #%d%
		   flag: 16
		   flags: 16
		XX,
	Dumper::toText($child),
);


// mixed
$child = new Child;
$child->flags = $child::B | $child::A | 16;

Assert::match(
	<<<'XX'
		Child #%d%
		   flag: unset
		   flags: self::A | self::B | 16 (19)
		XX,
	Dumper::toText($child),
);
