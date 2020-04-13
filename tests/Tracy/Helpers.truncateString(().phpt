<?php

/**
 * Test: Tracy\Helpers::truncateString()
 */

declare(strict_types=1);

use Tester\Assert;
use Tracy\Helpers;

require __DIR__ . '/../bootstrap.php';


Assert::same('', Helpers::truncateString('', 1));
Assert::same('h', Helpers::truncateString('hello', 1));
Assert::same('hello', Helpers::truncateString('hello', 5));
Assert::same('hello', Helpers::truncateString('hello', 6));

Assert::same('Iñtërnâtiônàlizætiøn', Helpers::truncateString('Iñtërnâtiônàlizætiøn', 20));
Assert::same('Iñtër', Helpers::truncateString('Iñtërnâtiônàlizætiøn', 5));

Assert::same("\x00", Helpers::truncateString("\x00\x01", 1));
Assert::same("\x00\x01", Helpers::truncateString("\x00\x01", 5));

Assert::same('bad', Helpers::truncateString("bad\xff", 3));
Assert::same("bad\xff", Helpers::truncateString("bad\xff", 4));
Assert::same("bad\xff", Helpers::truncateString("bad\xff", 5));
