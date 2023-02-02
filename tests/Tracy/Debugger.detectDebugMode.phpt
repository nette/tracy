<?php

/**
 * Test: Tracy\Debugger::detectDebugMode()
 */

declare(strict_types=1);

use Tester\Assert;
use Tracy\Debugger;

require __DIR__ . '/../bootstrap.php';



test('localhost', function () {
	unset($_SERVER['HTTP_X_FORWARDED_FOR']);

	$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
	Assert::true(Debugger::detectDebugMode());
	Assert::true(Debugger::detectDebugMode('192.168.1.1'));

	$_SERVER['REMOTE_ADDR'] = '::1';
	Assert::true(Debugger::detectDebugMode());

	$_SERVER['REMOTE_ADDR'] = '192.168.1.1';
	Assert::false(Debugger::detectDebugMode());
	Assert::false(Debugger::detectDebugMode('192.168.1.1.0'));
	Assert::true(Debugger::detectDebugMode('192.168.1.1'));
	Assert::true(Debugger::detectDebugMode('a,192.168.1.1,b'));
	Assert::true(Debugger::detectDebugMode('a 192.168.1.1 b'));

	Assert::false(Debugger::detectDebugMode([]));
	Assert::true(Debugger::detectDebugMode(['192.168.1.1']));
});


test('localhost + proxy', function () {
	$_SERVER['HTTP_X_FORWARDED_FOR'] = 'xx';

	$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
	Assert::false(Debugger::detectDebugMode());

	$_SERVER['REMOTE_ADDR'] = '::1';
	Assert::false(Debugger::detectDebugMode());

	$_SERVER['REMOTE_ADDR'] = '192.168.1.1';
	Assert::false(Debugger::detectDebugMode());
	Assert::true(Debugger::detectDebugMode($_SERVER['REMOTE_ADDR']));
});


test('missing $_SERVER[REMOTE_ADDR]', function () {
	unset($_SERVER['HTTP_X_FORWARDED_FOR'], $_SERVER['REMOTE_ADDR']);

	Assert::false(Debugger::detectDebugMode());
	Assert::false(Debugger::detectDebugMode('127.0.0.1'));

	Assert::true(Debugger::detectDebugMode(php_uname('n')));
	Assert::true(Debugger::detectDebugMode([php_uname('n')]));
});


test('secret', function () {
	unset($_SERVER['HTTP_X_FORWARDED_FOR']);
	$_SERVER['REMOTE_ADDR'] = '192.168.1.1';
	$_COOKIE[Debugger::CookieSecret] = '*secret*';

	Assert::false(Debugger::detectDebugMode());
	Assert::true(Debugger::detectDebugMode('192.168.1.1'));
	Assert::false(Debugger::detectDebugMode('abc@192.168.1.1'));
	Assert::true(Debugger::detectDebugMode('*secret*@192.168.1.1'));

	$_COOKIE[Debugger::CookieSecret] = ['*secret*'];
	Assert::false(Debugger::detectDebugMode('*secret*@192.168.1.1'));
});


test('', function () {
	unset($_SERVER['HTTP_X_FORWARDED_FOR']);
	$_SERVER['REMOTE_ADDR'] = 'xx';

	Debugger::enable();
	Assert::true(Debugger::$productionMode);

	Debugger::enable(true);
	Assert::true(Debugger::$productionMode);

	Debugger::enable(false);
	Assert::false(Debugger::$productionMode);

	Debugger::enable($_SERVER['REMOTE_ADDR']);
	Assert::false(Debugger::$productionMode);
});
