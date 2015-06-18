<?php

/**
 * Test: Tracy\Dumper::encodeString()
 */

use Tracy\Dumper;
use Tester\Assert;


require __DIR__ . '/../bootstrap.php';


Assert::same('', Dumper::encodeString(''));
Assert::same('hello', Dumper::encodeString('hello'));
Assert::same('hello', Dumper::encodeString('hello', 5));
Assert::same('hell ... ', Dumper::encodeString('hello', 4));

Assert::same('Iñtërnâtiônàlizætiøn', Dumper::encodeString('Iñtërnâtiônàlizætiøn'));
Assert::same('Iñtër ... ', Dumper::encodeString('Iñtërnâtiônàlizætiøn', 5));

Assert::same('\x00\x01', Dumper::encodeString("\x00\x01"));
Assert::same('\x00 ... ', Dumper::encodeString("\x00\x01", 1));
