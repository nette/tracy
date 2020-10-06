<?php

/**
 * Test: Tracy\Dumper::dump() in CLI
 */

declare(strict_types=1);

use Tester\Assert;
use Tracy\Debugger;
use Tracy\Dumper;


require __DIR__ . '/../bootstrap.php';

if (PHP_SAPI !== 'cli') {
	Tester\Environment::skip('Requires CLI mode');
}


class Capture extends php_user_filter
{
	public static $buffer = '';


	public function filter($in, $out, &$consumed, $closing)
	{
		while ($bucket = stream_bucket_make_writeable($in)) {
			self::$buffer .= $bucket->data;
			$consumed += $bucket->datalen;
			stream_bucket_append($out, $bucket);
		}
		return PSFS_PASS_ON;
	}
}

stream_filter_register('Capture', 'Capture');
stream_filter_append(STDOUT, 'Capture');


test('colors', function () {
	Dumper::$useColors = true;
	Capture::$buffer = '';
	Dumper::dump(123);
	Assert::match("\e[1;32m123\e[0m", Capture::$buffer);
});


test('no color', function () {
	Dumper::$useColors = false;
	Capture::$buffer = '';
	Dumper::dump(123);
	Assert::match('123', Capture::$buffer);
});


test('production mode', function () {
	Debugger::$productionMode = true;
	Capture::$buffer = '';
	ob_start();
	Dumper::dump('sensitive data');
	Assert::same('', Capture::$buffer);
	Assert::same('', ob_get_clean());
});


test('development mode', function () {
	Debugger::$productionMode = false;
	Capture::$buffer = '';
	Dumper::dump('sensitive data');
	Assert::match("'sensitive data'", Capture::$buffer);
});


test('returned value', function () {
	$obj = new stdClass;
	Assert::same(Dumper::dump($obj), $obj);
});

test('with custom scrubber', function () {
	// this test could be placed in a separate file,
	// but then `Capture` class and stream setup would have to be copied or refactored to a separate file
	$obj = (object) [
		'a' => 456,
		'password' => 'secret1',
		'PASSWORD' => 'secret2',
		'Pin' => 'secret3',
		'foo' => 'bar',
		'q' => 42,
		'inner' => [
			'a' => 123,
			'password' => 'secret4',
			'PASSWORD' => 'secret5',
			'Pin' => 'secret6',
			'bar' => 42,
		],
	];
	$scrubber = function (string $k, $v = null): bool {
		return strtolower($k) === 'pin' || strtolower($k) === 'foo' || $v === 42;
	};
	$expect = <<<'XX'
stdClass #%d%
   a: 456
   password: 'secret1'
   PASSWORD: 'secret2'
   Pin: ***** (string)
   foo: ***** (string)
   q: ***** (integer)
   inner: array (5)
   |  'a' => 123
   |  'password' => 'secret4'
   |  'PASSWORD' => 'secret5'
   |  'Pin' => ***** (string)
   |  'bar' => ***** (integer)
XX;
	Dumper::$useColors = false;
	Capture::$buffer = '';

	Dumper::$scrubber = $scrubber;
	Dumper::dump($obj);
	Assert::match($expect, Capture::$buffer);
});
