<?php

/**
 * Test: Tracy\Dumper::toText() with location
 */

declare(strict_types=1);

use Tester\Assert;
use Tracy\Dumper;

require __DIR__ . '/../bootstrap.php';


class Test
{
}

Assert::match('Test #%a%
in %a%:%d%', Dumper::toText(new Test, ['location' => true]));


Assert::match('Test #%a%', Dumper::toText(new Test, ['location' => false]));


Assert::match('Test #%a%', Dumper::toText(new Test, ['location' => Dumper::LOCATION_SOURCE]));


Assert::match('Test #%a%', Dumper::toText(new Test, ['location' => Dumper::LOCATION_CLASS]));


Assert::match('Test #%a%
in %a%:%d%', Dumper::toText(new Test, ['location' => Dumper::LOCATION_LINK | Dumper::LOCATION_CLASS]));
