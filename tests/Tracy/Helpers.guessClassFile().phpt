<?php

/**
 * Test: Tracy\Helpers::guessClassFile()
 */

declare(strict_types=1);

use Tester\Assert;
use Tracy\Helpers;

require __DIR__ . '/../bootstrap.php';
require __DIR__ . '/fixtures/guess-class.php';

$ds = DIRECTORY_SEPARATOR;

Assert::same(null, Helpers::guessClassFile('A'));
Assert::same(null, Helpers::guessClassFile('A\B'));
Assert::same(__DIR__ . "{$ds}fixtures{$ds}C.php", Helpers::guessClassFile('A\B\C'));
Assert::same(__DIR__ . "{$ds}fixtures{$ds}C{$ds}D.php", Helpers::guessClassFile('A\B\C\D'));

Assert::same(null, Helpers::guessClassFile('X'));
Assert::same(null, Helpers::guessClassFile(''));
Assert::same(null, Helpers::guessClassFile('stdClass'));
Assert::same(null, Helpers::guessClassFile('stdClass\X'));
