<?php

/**
 * Test: Tracy\Dumper::toText() with location
 */

declare(strict_types=1);

use Tester\Assert;
use Tracy\Dumper;

require __DIR__ . '/../bootstrap.php';


Assert::same("true\nin " . __FILE__ . ':' . __LINE__ . "\n", Dumper::toText(true, ['location' => true]));


Assert::same("array (0)\nin " . __FILE__ . ':' . __LINE__ . "\n", Dumper::toText([], ['location' => true]));


Assert::same("array (1)\n   0 => 1\nin " . __FILE__ . ':' . __LINE__ . "\n", Dumper::toText([1], ['location' => true]));


class Test
{
}

Assert::match('Test #%d%
in %a%:%d%', Dumper::toText(new Test, ['location' => true]));


Assert::match('Test #%d%', Dumper::toText(new Test, ['location' => false]));


Assert::match('Test #%d%
in %a%:%d%', Dumper::toText(new Test, ['location' => Dumper::LOCATION_SOURCE]));


Assert::match('Test #%d%', Dumper::toText(new Test, ['location' => Dumper::LOCATION_CLASS]));
