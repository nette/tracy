<?php declare(strict_types=1);

/**
 * Test: Tracy\BlueScreen::renderAgent() full output
 * @outputMatchFile expected/BlueScreen.renderAgent().output.expect
 */

require __DIR__ . '/../bootstrap.php';


function first($arg1, $arg2)
{
	second(true, false);
}


function second($arg1, $arg2)
{
	third([1, 2, 3]);
}


function third($arg1)
{
	throw new Exception('The my exception', 123);
}


$bs = new Tracy\BlueScreen;
$bs->showEnvironment = false;

try {
	first(10, 'any string');
} catch (\Throwable $e) {
	echo $bs->renderAgent($e);
}
