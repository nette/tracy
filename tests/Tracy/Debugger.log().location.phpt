<?php declare(strict_types=1);

/**
 * Test: Tracy\Debugger::log() records the caller location in the BlueScreen report.
 */

use Tester\Assert;
use Tracy\Debugger;

require __DIR__ . '/../bootstrap.php';


Tester\Helpers::purge(getTempDir());
Debugger::$logDirectory = getTempDir();


test('Debugger::log() writes the call site into the report as "Logged from"', function () {
	$line = __LINE__ + 1;
	$file = Debugger::log(new Exception('location test'), Debugger::EXCEPTION);
	Assert::type('string', $file);

	$html = file_get_contents($file);
	Assert::match('%A%Logged from <a%A%<b>Debugger.log().location.phpt</b>:' . $line . '</a>%A%', $html);

	$md = file_get_contents(substr($file, 0, -5) . '.md');
	Assert::match('This is an error page%A%Logged from: %a%Debugger.log().location.phpt:' . $line . '%A%', $md);
});
