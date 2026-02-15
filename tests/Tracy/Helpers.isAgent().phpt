<?php

/**
 * Test: Tracy\Helpers::isAgent()
 */

declare(strict_types=1);

use Tester\Assert;
use Tracy\Helpers;

require __DIR__ . '/../bootstrap.php';


test('returns false when no Accept header', function () {
	unset($_SERVER['HTTP_ACCEPT']);
	Assert::false(Helpers::isAgent());
});

test('returns false when Accept is empty', function () {
	$_SERVER['HTTP_ACCEPT'] = '';
	Assert::false(Helpers::isAgent());
});

test('returns false for browser Accept with text/html', function () {
	$_SERVER['HTTP_ACCEPT'] = 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8';
	Assert::false(Helpers::isAgent());
});

test('returns true for curl default Accept */*', function () {
	$_SERVER['HTTP_ACCEPT'] = '*/*';
	Assert::true(Helpers::isAgent());
});

test('returns true for text/plain', function () {
	$_SERVER['HTTP_ACCEPT'] = 'text/plain';
	Assert::true(Helpers::isAgent());
});

test('returns true for text/markdown', function () {
	$_SERVER['HTTP_ACCEPT'] = 'text/markdown';
	Assert::true(Helpers::isAgent());
});

test('returns true for application/json', function () {
	$_SERVER['HTTP_ACCEPT'] = 'application/json';
	Assert::true(Helpers::isAgent());
});
