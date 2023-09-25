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

Assert::same('<i>\x00\x01</i>', Helpers::encodeString("\x00\x01"));
Assert::same('<i>\x00</i> <span>…</span> 1234567890', Helpers::encodeString("\x00\x01 12345678901234567890", 1));

Assert::same("utf <i>\\n</i>\n<i>\\r\\t</i>    <i>\\e\\x00</i> Iñtër", Helpers::encodeString("utf \n\r\t\e\x00 Iñtër"));
Assert::same('utf \n\r\t\xab Iñtër', Helpers::encodeString('utf \n\r\t\xab Iñtër'));
Assert::same("binary <i>\\n</i>\n<i>\\r\\t</i>    <i>\\xA0</i> I<i>\\xC3\\xB1</i>t<i>\\xC3\\xAB</i>r", Helpers::encodeString("binary \n\r\t\xA0 Iñtër"));

Assert::same("utf \n\r\t<i>\\e\\x00</i> Iñtër", Helpers::encodeString("utf \n\r\t\e\x00 Iñtër", showWhitespaces: false));
Assert::same("binary \n\r\t<i>\\xA0</i> I<i>\\xC3\\xB1</i>t<i>\\xC3\\xAB</i>r", Helpers::encodeString("binary \n\r\t\xA0 Iñtër", showWhitespaces: false));
