<?php declare(strict_types=1);

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


test('dumper with default keysToHide scrubbing', function () {
	$blueScreen = new Tracy\BlueScreen;
	$dumper = $blueScreen->getDumper();
	Assert::contains('foo', $dumper('foo', 'bar'));
	Assert::notContains('secret', $dumper('secret', 'password'));
	Assert::notContains('secret', $dumper('secret', 'PiN'));
});

test('dumper with custom scrubbing', function () {
	$blueScreen = new Tracy\BlueScreen;
	$blueScreen->scrubber = fn(string $k, $v = null): bool => strtolower($k) === 'pin' || strtolower($k) === 'foo' || $v === 42;
	$dumper = $blueScreen->getDumper();
	Assert::contains('foo', $dumper('foo', 'bar'));
	Assert::notContains('secret', $dumper('secret', 'password')); // default keysToHide

	Assert::notContains('secret', $dumper('secret', 'PiN')); // scrubbed by key
	Assert::notContains('42', $dumper(42, 'bar')); // scrubbed by value
});

test('dumper with regexp scrubbing', function () {
	$blueScreen = new Tracy\BlueScreen;
	$blueScreen->scrubber = fn(string $k): bool => (bool) preg_match('#password#i', $k);
	$dumper = $blueScreen->getDumper();
	Assert::contains('foo', $dumper('foo', 'bar'));
	Assert::notContains('secret', $dumper('secret', 'super_password'));

	$fix = [
		'password' => 'secret',
		'foo' => 'foo ok',
		'password_check' => 'secret',
		'DATABASE_PASSWORD' => 'secret',
	];

	Assert::notContains('secret', $dumper($fix));
	Assert::contains('foo ok', $dumper($fix));
});


// getAgentDumper() tests

test('agent dumper returns plain text', function () {
	$blueScreen = new Tracy\BlueScreen;
	$dumper = $blueScreen->getAgentDumper();
	$output = $dumper('foo');
	Assert::contains('foo', $output);
	Assert::notContains('<', $output); // no HTML tags
});

test('agent dumper with default keysToHide scrubbing', function () {
	$blueScreen = new Tracy\BlueScreen;
	$dumper = $blueScreen->getAgentDumper();
	Assert::contains('foo', $dumper('foo', 'bar'));
	Assert::notContains('secret', $dumper('secret', 'password'));
	Assert::notContains('secret', $dumper('secret', 'PiN'));
});

test('agent dumper with custom scrubber', function () {
	$blueScreen = new Tracy\BlueScreen;
	$blueScreen->scrubber = fn(string $k, $v = null): bool => strtolower($k) === 'token';
	$dumper = $blueScreen->getAgentDumper();
	Assert::contains('foo', $dumper('foo', 'bar'));
	Assert::notContains('secret', $dumper('secret', 'password')); // default keysToHide
	Assert::notContains('my-token', $dumper('my-token', 'token')); // custom scrubber
});

test('agent dumper respects depth 3', function () {
	$blueScreen = new Tracy\BlueScreen;
	$dumper = $blueScreen->getAgentDumper();
	$deep = ['a' => ['b' => ['c' => ['d' => 'deep']]]]; // 4 levels
	$output = $dumper($deep);
	Assert::contains("'c'", $output);
	Assert::contains('...', $output);
	Assert::notContains("'deep'", $output);
});
