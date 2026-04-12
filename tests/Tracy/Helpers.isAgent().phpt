<?php

/**
 * Test: Tracy\Helpers::isAgent()
 */

declare(strict_types=1);

use Tester\Assert;
use Tracy\Helpers;

require __DIR__ . '/../bootstrap.php';


test('returns false when no cookie', function () {
	unset($_COOKIE['tracy-webdriver']);
	Assert::false(Helpers::isAgent());
});

test('returns false when cookie is empty', function () {
	$_COOKIE['tracy-webdriver'] = '';
	Assert::false(Helpers::isAgent());
});

test('returns false when cookie has wrong value', function () {
	$_COOKIE['tracy-webdriver'] = '0';
	Assert::false(Helpers::isAgent());
});

test('returns true when cookie is set to 1', function () {
	$_COOKIE['tracy-webdriver'] = '1';
	Assert::true(Helpers::isAgent());
});
