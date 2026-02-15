<?php

/**
 * Test: Tracy\Bar::renderAgent()
 */

declare(strict_types=1);

use Tester\Assert;
use Tracy\Debugger;

require __DIR__ . '/../bootstrap.php';


test('info line with time and memory', function () {
	Debugger::$productionMode = false;
	Debugger::$time = microtime(true) - 0.05; // simulate 50 ms
	$bar = Debugger::getBar();
	Assert::match(
		"Tracy Bar | %a% ms | %a% MB\n",
		$bar->renderAgent(),
	);
});
