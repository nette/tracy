<?php declare(strict_types=1);

/**
 * Test: Tracy\BlueScreen::prepareStack()
 */

use Tester\Assert;
use Tracy\BlueScreen;
use Tracy\Debugger;

require __DIR__ . '/../bootstrap.php';
require __DIR__ . '/fixtures/transparentWrapper.php';


$blueScreen = new BlueScreen;


test('exception thrown in user code returns null expanded', function () use ($blueScreen) {
	$ex = new Exception('boom');
	[$stack, $expanded] = $blueScreen->prepareStack($ex);
	Assert::same($ex->getTrace(), $stack);
	Assert::null($expanded);
});


test('exception in transparent path returns first user frame index', function () use ($blueScreen) {
	$original = Debugger::$transparentPaths;
	Debugger::$transparentPaths[] = __DIR__ . '/fixtures';
	try {
		$ex = Tracy\TestFixtures\makeException();
		[$stack, $expanded] = $blueScreen->prepareStack($ex);
		Assert::notNull($expanded);
		Assert::false(str_contains($stack[$expanded]['file'] ?? '', '/fixtures/'));
	} finally {
		Debugger::$transparentPaths = $original;
	}
});


test('fatal-level ErrorException in transparent path does not expand', function () use ($blueScreen) {
	$original = Debugger::$transparentPaths;
	Debugger::$transparentPaths[] = __DIR__ . '/fixtures';
	try {
		$ex = new ErrorException('fatal', 0, E_ERROR, __DIR__ . '/fixtures/any.php', 1);
		[, $expanded] = $blueScreen->prepareStack($ex);
		Assert::null($expanded);
	} finally {
		Debugger::$transparentPaths = $original;
	}
});
