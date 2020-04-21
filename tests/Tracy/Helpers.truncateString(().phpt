<?php

/**
 * Test: Tracy\Helpers::truncateString()
 */

declare(strict_types=1);

use Tester\Assert;
use Tracy\Helpers;


require __DIR__ . '/../bootstrap.php';


Assert::same('', Helpers::truncateString('', 1, true));
Assert::same('h', Helpers::truncateString('hello', 1, true));
Assert::same('hello', Helpers::truncateString('hello', 5, true));
Assert::same('hello', Helpers::truncateString('hello', 6, true));

Assert::same('Iñtërnâtiônàlizætiøn', Helpers::truncateString('Iñtërnâtiônàlizætiøn', 20, true));
Assert::same('Iñtër', Helpers::truncateString('Iñtërnâtiônàlizætiøn', 5, true));

Assert::same("\x00", Helpers::truncateString("\x00\x01", 1, true));
Assert::same("\x00\x01", Helpers::truncateString("\x00\x01", 5, true));

Assert::same('bad', Helpers::truncateString("bad\xff", 3, false));
Assert::same("bad\xff", Helpers::truncateString("bad\xff", 4, false));
Assert::same("bad\xff", Helpers::truncateString("bad\xff", 5, false));
