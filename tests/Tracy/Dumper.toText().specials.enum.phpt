<?php

/**
 * Test: Tracy\Dumper::toText() enums
 * @phpVersion 8.1
 */

declare(strict_types=1);

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
	case GET = 'get';
	case POST = 'post';
}

Assert::match(<<<'XX'
array (3)
   0 => Suit::Clubs #%d%
   1 => Methods::GET #%d%
   |  value: 'get'
   2 => Methods::GET #%d% see above
XX
	, Dumper::toText([Suit::Clubs, Methods::GET, Methods::GET]));
