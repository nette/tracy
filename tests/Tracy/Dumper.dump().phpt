<?php

/**
 * Test: Tracy\Dumper::dump() modes
 */

declare(strict_types=1);

use Tester\Assert;
use Tracy\Dumper;


require __DIR__ . '/../bootstrap.php';


test(function () { // html mode
	header('Content-Type: text/html');
	if (headers_list()) {
		ob_start();
		Assert::same(123, Dumper::dump(123));
		Assert::match('<pre class="tracy-dump"><span class="tracy-dump-number">123</span>
</pre>', ob_get_clean());
	}
});


test(function () { // terminal mode
	header('Content-Type: text/plain');
	putenv('ConEmuANSI=ON');
	ob_start();
	Assert::same(123, Dumper::dump(123));
	Assert::match("\x1b[1;32m123\x1b[0m", ob_get_clean());
});


test(function () { // text mode
	header('Content-Type: text/plain');
	Tracy\Dumper::$terminalColors = null;
	ob_start();
	Assert::same(123, Dumper::dump(123));
	Assert::match('123', ob_get_clean());
});
