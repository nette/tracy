<?php declare(strict_types=1);

/**
 * Test: Tracy\Dumper exposers for modern PHP constructs (hooks, WeakReference, Uri, CurlHandle)
 */

use Tester\Assert;
use Tracy\Dumper;

require __DIR__ . '/../bootstrap.php';


test('virtual hooked property is marked, not shown as unset', function () {
	if (PHP_VERSION_ID < 80400) {
		Tester\Environment::skip('Requires PHP 8.4');
	}

	$obj = eval('return new class {
		public string $virtual { get => "computed"; }
		public string $plain = "a";
	};');

	Assert::match(
		<<<'XX'
			class@anonymous #%d%
			   virtual: {virtual}
			   plain: 'a'
			XX,
		Dumper::toText($obj),
	);
});


test('WeakReference shows the target object or (dead)', function () {
	$obj = new stdClass;
	$ref = WeakReference::create($obj);
	Assert::match(
		<<<'XX'
			WeakReference #%d%
			   object: stdClass #%d%
			XX,
		Dumper::toText($ref),
	);

	$dead = WeakReference::create(new stdClass);
	Assert::match('WeakReference (dead) #%d%', Dumper::toText($dead));
});


test('closure shows return type and bound $this', function () {
	$host = new class {
		public function make(): Closure
		{
			return fn(int $x): string => 'a';
		}
	};

	Assert::match(
		<<<'XX'
			Closure($x) #%d%
			   this: class@anonymous
			XX,
		Dumper::toText($host->make()),
	);
});


test('Uri classes show the address and components', function () {
	if (!class_exists(Uri\Rfc3986\Uri::class)) {
		Tester\Environment::skip('Requires PHP 8.5 uri extension');
	}

	Assert::match(
		<<<'XX'
			Uri\Rfc3986\Uri #%d%
			   uri: 'https://example.com:8080/p?q=1#f'
			   scheme: 'https'
			   host: 'example.com'
			   port: 8080
			   path: '/p'
			   query: 'q=1'
			   fragment: 'f'
			XX,
		Dumper::toText(Uri\Rfc3986\Uri::parse('https://example.com:8080/p?q=1#f')),
	);

	Assert::match(
		<<<'XX'
			Uri\WhatWg\Url #%d%
			   uri: 'https://example.com/p'
			   scheme: 'https'
			   host: 'example.com'
			   path: '/p'
			XX,
		Dumper::toText(Uri\WhatWg\Url::parse('https://example.com/p')),
	);
});


test('CurlHandle shows request info', function () {
	if (!extension_loaded('curl')) {
		Tester\Environment::skip('Requires curl extension');
	}

	$curl = curl_init('http://example.com');
	Assert::match("CurlHandle #%d%\n   url: 'http://example.com'%A%", Dumper::toText($curl));
});
