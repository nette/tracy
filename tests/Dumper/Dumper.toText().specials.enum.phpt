<?php declare(strict_types=1);

/**
 * Test: Tracy\Dumper::toText() enums
 */

use Tester\Assert;
use Tracy\Dumper;

require __DIR__ . '/../bootstrap.php';


enum Suit
{
	case Clubs;
	case Diamonds;
	case Hearts;
	case Spades;
}

enum Methods: string
{
	case Get = 'get';
	case Post = 'post';
}

Assert::match(
	<<<'XX'
		array (3)
		   0 => Suit::Clubs #%d%
		   1 => Methods::Get #%d%
		   |  value: 'get'
		   2 => Methods::Get #%d% see above
		XX,
	Dumper::toText([Suit::Clubs, Methods::Get, Methods::Get]),
);
