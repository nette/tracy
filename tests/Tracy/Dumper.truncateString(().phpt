<?php

/**
 * Test: Tracy\Dumper::truncateString()
 */

declare(strict_types=1);

use Tester\Assert;
use Tracy\Dumper;


require __DIR__ . '/../bootstrap.php';


Assert::same('', Dumper::truncateString('', 1));
Assert::same('h', Dumper::truncateString('hello', 1));
Assert::same('hello', Dumper::truncateString('hello', 5));
Assert::same('hello', Dumper::truncateString('hello', 6));

Assert::same('Iñtërnâtiônàlizætiøn', Dumper::truncateString('Iñtërnâtiônàlizætiøn', 20));
Assert::same('Iñtër', Dumper::truncateString('Iñtërnâtiônàlizætiøn', 5));

Assert::same("\x00", Dumper::truncateString("\x00\x01", 1));
Assert::same("\x00\x01", Dumper::truncateString("\x00\x01", 5));

Assert::same("bad", Dumper::truncateString("bad\xff", 3));
Assert::same("bad\xff", Dumper::truncateString("bad\xff", 4));
Assert::same("bad\xff", Dumper::truncateString("bad\xff", 5));
