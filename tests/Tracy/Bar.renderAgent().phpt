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


test('dumps rendering', function () {
	$bar = new Tracy\Bar;
	$panel = new Tracy\DefaultBarPanel('dumps');
	$panel->data = [
		['title' => 'My Variable', 'dump' => '<pre>irrelevant html</pre>', 'text' => "array (2)\n   0 => 1\n   1 => 2\n"],
		['title' => null, 'dump' => '<pre>html</pre>', 'text' => "'hello'\n"],
	];
	$bar->addPanel($panel, 'Tracy:dumps');

	$output = $bar->renderAgent();
	Assert::contains('## Dumps', $output);
	Assert::contains('### My Variable', $output);
	Assert::contains('array (2)', $output);
	Assert::contains("'hello'", $output);
});


test('dumps text respects depth limit', function () {
	$_COOKIE['tracy-webdriver'] = '1';

	Debugger::$productionMode = false;
	Debugger::$maxDepth = 15;
	$deep = ['a' => ['b' => ['c' => ['d' => 'deep']]]];
	Debugger::barDump($deep, 'Deep');

	// get the panel from Debugger's bar, which has the real data
	$realPanel = Debugger::getBar()->getPanel('Tracy:dumps');
	$last = end($realPanel->data);

	Assert::contains("'c'", $last['text']);
	Assert::contains('...', $last['text']);
	Assert::notContains("'deep'", $last['text']);

	unset($_COOKIE['tracy-webdriver']);
});


test('no dumps section when empty', function () {
	$bar = new Tracy\Bar;
	$panel = new Tracy\DefaultBarPanel('dumps');
	$panel->data = [];
	$bar->addPanel($panel, 'Tracy:dumps');

	Assert::match(
		"Tracy Bar | %a% ms | %a% MB\n",
		$bar->renderAgent(),
	);
});
