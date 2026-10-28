<?php declare(strict_types=1);

/**
 * Test: Tracy\BlueScreen::renderToFile()
 */

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


test('output buffers are closed and the file is removed when rendering fails', function () {
	$blueScreen = new Tracy\BlueScreen;
	$blueScreen->scrubber = fn() => throw new LogicException('Scrubber failed');
	$file = getTempDir() . '/failed.html';
	$obLevel = ob_get_level();

	Assert::exception(
		fn() => $blueScreen->renderToFile(new Exception('Oops'), $file),
		LogicException::class,
		'Scrubber failed',
	);
	Assert::same($obLevel, ob_get_level());
	Assert::false(is_file($file));
});
