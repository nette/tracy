<?php declare(strict_types=1);

/**
 * Test: Tracy\Debugger::dump() console output for AI agents.
 */

use Tester\Assert;
use Tracy\Debugger;

require __DIR__ . '/../bootstrap.php';

if (PHP_SAPI === 'cli') {
	Tester\Environment::skip('Requires CGI mode');
}


setHtmlMode();
$_COOKIE['tracy-webdriver'] = '1';
Debugger::$productionMode = false;

test('agent gets console.log output', function () {
	ob_start();
	Debugger::dump('hello');
	$output = ob_get_clean();

	Assert::contains('<tracy-div>', $output);
	Assert::contains('<script', $output);
	Assert::contains('console.log(', $output);
	Assert::contains('hello', $output);
});


test('agent text dump respects depth limit', function () {
	Debugger::$maxDepth = 15; // HTML depth is high

	$deep = ['a' => ['b' => ['c' => ['d' => 'deep']]]]; // 4 levels

	ob_start();
	Debugger::dump($deep);
	$output = ob_get_clean();

	// extract the console.log JSON payload
	preg_match('#console\.log\((".*?")\)#s', $output, $m);
	$text = json_decode($m[1]);

	// depth 3: level 4 ('d' => 'deep') should be truncated
	Assert::contains("'c'", $text);
	Assert::contains('...', $text);
	Assert::notContains("'deep'", $text);
});


test('return mode does not output console.log', function () {
	ob_start();
	$result = Debugger::dump([1, 2], return: true);
	$output = ob_get_clean();

	Assert::same('', $output);
	Assert::type('string', $result);
});
