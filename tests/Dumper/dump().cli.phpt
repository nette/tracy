<?php

/**
 * Test: dump() in CLI
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
	putenv('FORCE_COLOR=1');
	Capture::$buffer = '';
	dump(123);
	Assert::match("\e[1;32m123\e[0m", Capture::$buffer);
});


test('no color', function () {
	Dumper::$terminalColors = null;
	Capture::$buffer = '';
	dump(123);
	Assert::match('123', Capture::$buffer);
});


test('production mode', function () {
	Debugger::$productionMode = true;
	Capture::$buffer = '';
	ob_start();
	dump('sensitive data');
	Assert::same('', Capture::$buffer);
	Assert::same('', ob_get_clean());
});


test('development mode', function () {
	Debugger::$productionMode = false;
	Capture::$buffer = '';
	dump('sensitive data');
	Assert::match("'sensitive data'", Capture::$buffer);
});


test('returned value', function () {
	$obj = new stdClass;
	Assert::same(dump($obj), $obj);
});
