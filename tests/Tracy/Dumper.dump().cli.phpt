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


test(function () { // colors
	Dumper::$useColors = true;
	Capture::$buffer = '';
	Dumper::dump(123);
	Assert::match("\e[1;32m123\e[0m", Capture::$buffer);
});


test(function () { // no color
	Dumper::$useColors = false;
	Capture::$buffer = '';
	Dumper::dump(123);
	Assert::match('123', Capture::$buffer);
});


test(function () { // production mode
	Debugger::$productionMode = true;
	Capture::$buffer = '';
	ob_start();
	Dumper::dump('sensitive data');
	Assert::same('', Capture::$buffer);
	Assert::same('', ob_get_clean());
});


test(function () { // development mode
	Debugger::$productionMode = false;
	Capture::$buffer = '';
	Dumper::dump('sensitive data');
	Assert::match("'sensitive data'", Capture::$buffer);
});


test(function () { // returned value
	$obj = new stdClass;
	Assert::same(Dumper::dump($obj), $obj);
});
