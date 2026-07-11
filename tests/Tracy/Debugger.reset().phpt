<?php declare(strict_types=1);

/**
 * Test: Tracy\Debugger::reset() for long-running workers
 */

use Tester\Assert;
use Tracy\Debugger;

require __DIR__ . '/../bootstrap.php';


test('timers are cleared', function () {
	Debugger::timer('job');
	usleep(1000);
	Assert::true(Debugger::timer('job') > 0);

	Debugger::timer('job');
	Debugger::reset();
	usleep(1000);
	Assert::same(0.0, Debugger::timer('job')); // starts fresh after reset
});


test('request time is re-stamped', function () {
	Debugger::$time = 123.0;
	Debugger::reset();
	Assert::true(Debugger::$time > 123.0);
});


test('barDump data is cleared', function () {
	Debugger::$productionMode = false;
	Debugger::barDump('hello');
	$panel = Debugger::getBar()->getPanel('Tracy:dumps');
	Assert::count(1, $panel->data);

	Debugger::reset();
	Assert::null($panel->data);

	Debugger::barDump('world'); // works again after reset
	Assert::count(1, $panel->data);
});
