<?php declare(strict_types=1);

/**
 * Test: Tracy\Helpers::getNonce()
 */

use Tester\Assert;
use Tracy\Helpers;

require __DIR__ . '/../bootstrap.php';

if (PHP_SAPI === 'cli') {
	Tester\Environment::skip('Requires CGI mode');
}

ob_start();

test('no CSP header', function () {
	Assert::null(Helpers::getNonce());
});


test('script-src with nonce', function () {
	header("Content-Security-Policy: script-src 'nonce-abc123='");
	Assert::same('abc123=', Helpers::getNonce());
});


test('script-src-elem with nonce', function () {
	header("Content-Security-Policy: script-src-elem 'nonce-xyz789'");
	Assert::same('xyz789', Helpers::getNonce());
});


test('script-src-elem with nonce and script-src without', function () {
	header("Content-Security-Policy: script-src 'self'; script-src-elem 'nonce-elem456'");
	Assert::same('elem456', Helpers::getNonce());
});


test('script-src with nonce and script-src-elem without', function () {
	header("Content-Security-Policy: script-src-elem 'self'; script-src 'nonce-fallback1'");
	Assert::same('fallback1', Helpers::getNonce());
});


test('script-src-attr is ignored', function () {
	header("Content-Security-Policy: script-src-attr 'nonce-bad123'");
	Assert::null(Helpers::getNonce());
});


test('no nonce in script-src', function () {
	header("Content-Security-Policy: script-src 'self'");
	Assert::null(Helpers::getNonce());
});


test('Report-Only header', function () {
	header_remove('Content-Security-Policy');
	header("Content-Security-Policy-Report-Only: script-src 'nonce-report789'");
	Assert::same('report789', Helpers::getNonce());
});
