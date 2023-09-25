<?php

/**
 * Test: Tracy\Helpers::truncateString()
 */

declare(strict_types=1);

use Tester\Assert;
use Tracy\Helpers;

require __DIR__ . '/../bootstrap.php';


Assert::same('', Helpers::truncateString('', 1, utf8: true));
Assert::same('h', Helpers::truncateString('hello', 1, utf8: true));
Assert::same('hello', Helpers::truncateString('hello', 5, utf8: true));
Assert::same('hello', Helpers::truncateString('hello', 6, utf8: true));

Assert::same('Iñtërnâtiônàlizætiøn', Helpers::truncateString('Iñtërnâtiônàlizætiøn', 20, utf8: true));
Assert::same('Iñtër', Helpers::truncateString('Iñtërnâtiônàlizætiøn', 5, utf8: true));

Assert::same("\x00", Helpers::truncateString("\x00\x01", 1, utf8: true));
Assert::same("\x00\x01", Helpers::truncateString("\x00\x01", 5, utf8: true));

Assert::same('bad', Helpers::truncateString("bad\xff", 3, utf8: false));
Assert::same("bad\xff", Helpers::truncateString("bad\xff", 4, utf8: false));
Assert::same("bad\xff", Helpers::truncateString("bad\xff", 5, utf8: false));
