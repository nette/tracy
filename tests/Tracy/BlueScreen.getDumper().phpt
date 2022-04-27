<?php

declare(strict_types=1);


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
