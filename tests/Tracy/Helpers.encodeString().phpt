<?php

/**
 * Test: Tracy\Helpers::encodeString()
 */

declare(strict_types=1);

use Tester\Assert;
use Tracy\Helpers;

require __DIR__ . '/../bootstrap.php';


Assert::same('', Helpers::encodeString(''));
Assert::same('hello', Helpers::encodeString('hello'));
Assert::same('hello', Helpers::encodeString('hello', 5));
Assert::same('hell ... ', Helpers::encodeString('hello', 4));

Assert::same('Iñtërnâtiônàlizætiøn', Helpers::encodeString('Iñtërnâtiônàlizætiøn'));
Assert::same('Iñtër ... ', Helpers::encodeString('Iñtërnâtiônàlizætiøn', 5));

Assert::same('\x00\x01', Helpers::encodeString("\x00\x01"));
Assert::same('\x00 ... ', Helpers::encodeString("\x00\x01", 1));
