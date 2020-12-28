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
Assert::same('hello', Helpers::encodeString('hello', 4));
Assert::same('hell <span>…</span> 1234567890', Helpers::encodeString('hello 12345678901234567890', 4));

Assert::same('Iñtërnâtiônàlizætiøn', Helpers::encodeString("I\u{F1}t\u{EB}rn\u{E2}ti\u{F4}n\u{E0}liz\u{E6}ti\u{F8}n"));
Assert::same('Iñtër <span>…</span> 1234567890', Helpers::encodeString("I\u{F1}t\u{EB}rn\u{E2}ti\u{F4}n\u{E0}liz\u{E6}ti\u{F8}n 1234567890", 5));

Assert::same('<span>\x00\x01</span>', Helpers::encodeString("\x00\x01"));
Assert::same('<span>\x00</span> <span>…</span> 1234567890', Helpers::encodeString("\x00\x01 12345678901234567890", 1));

Assert::same("utf <span>\\n</span>\n<span>\\r\\t</span>    <span>\\e\\x00</span> Iñtër", Helpers::encodeString("utf \n\r\t\e\x00 Iñtër"));
Assert::same('utf \n\r\t\xab Iñtër', Helpers::encodeString('utf \n\r\t\xab Iñtër'));
Assert::same("binary <span>\\n</span>\n<span>\\r\\t</span>    <span>\\xA0</span> I<span>\\xC3\\xB1</span>t<span>\\xC3\\xAB</span>r", Helpers::encodeString("binary \n\r\t\xA0 Iñtër"));
