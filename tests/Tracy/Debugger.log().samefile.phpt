<?php

/**
 * Test: Tracy\Debugger logging exceptions in log message.
 */

declare(strict_types=1);

use Tester\Assert;
use Tracy\Debugger;

require __DIR__ . '/../bootstrap.php';


// Setup environment
Debugger::$logDirectory = getTempDir();


function foo($fp)
{
	throw new Exception;
}


for ($i = 0; $i < 3; $i++) {
	$path = getTempDir() . "/$i";
	try {
		$files[] = $file = fopen(getTempDir() . "/$i", 'w');
		foo($file);
	} catch (Throwable $e) {
		$name[] = Debugger::log($e);
	}
}

while (--$i > 0) {
	Assert::same($name[0], $name[$i]);
}
