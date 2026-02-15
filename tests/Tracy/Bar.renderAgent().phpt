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


test('warnings rendering', function () {
	$bar = new Tracy\Bar;
	$panel = new Tracy\DefaultBarPanel('warnings');
	$panel->data = [
		'/app/foo.php|10|Notice: Undefined variable $x' => 3,
		'/app/bar.php|25|Deprecated: implode()' => 1,
	];
	$bar->addPanel($panel, 'Tracy:warnings');

	Assert::match(
		"Tracy Bar | %a% ms | %a% MB\n\n## Warnings\n\n- Notice: Undefined variable \$x in /app/foo.php:10 (\u{00d7}3)\n- Deprecated: implode() in /app/bar.php:25\n\n",
		$bar->renderAgent(),
	);
});


test('no warnings section when empty', function () {
	$bar = new Tracy\Bar;
	$panel = new Tracy\DefaultBarPanel('warnings');
	$bar->addPanel($panel, 'Tracy:warnings');

	Assert::match(
		"Tracy Bar | %a% ms | %a% MB\n",
		$bar->renderAgent(),
	);
});
