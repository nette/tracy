<?php declare(strict_types=1);

/**
 * Test: Tracy\Helpers::findCallerLocation()
 *
 * Wrapper-only assertions; predicate semantics are covered in countTransparentFrames.phpt.
 */

use Tester\Assert;
use Tracy\Helpers;

require __DIR__ . '/../bootstrap.php';
require __DIR__ . '/fixtures/transparentWrapper.php';


test('returns caller file and line', function () {
	$line = __LINE__ + 1;
	$location = Helpers::findCallerLocation([]);
	Assert::same(__FILE__, $location['file']);
	Assert::same($line, $location['line']);
});


test('returns the call site above a transparent wrapper', function () {
	$line = __LINE__ + 1;
	$location = Tracy\TestFixtures\findCallerLocationWrapper();
	Assert::same(__FILE__, $location['file']);
	Assert::same($line, $location['line']);
});
