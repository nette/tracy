<?php declare(strict_types=1);

/**
 * Test: Tracy\BlueScreen::renderToAjax() agent output.
 */

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';

if (PHP_SAPI === 'cli') {
	Tester\Environment::skip('Requires CGI mode');
}


class MockSessionStorage2 implements Tracy\SessionStorage
{
	public array $data = [];


	public function isAvailable(): bool
	{
		return true;
	}


	public function &getData(): array
	{
		return $this->data;
	}
}


function createTestException(): Throwable
{
	return new Exception('Test error');
}


// AJAX exception includes console.error for agent
test('', function () {
	$_SERVER['HTTP_X_TRACY_AJAX'] = 'abcdef1234';
	$_COOKIE['tracy-webdriver'] = '1';

	$storage = new MockSessionStorage2;
	$defer = new Tracy\DeferredContent($storage);
	$defer->sendAssets();

	$bs = new Tracy\BlueScreen;
	$bs->renderToAjax(createTestException(), $defer);

	$code = $storage->data['setup']['abcdef1234']['code'] ?? '';
	Assert::contains('Tracy.BlueScreen.loadAjax(', $code);

	// Check that console.error setup call was added (after the loadAjax call)
	$statements = explode(";\n", $code);
	Assert::true(count($statements) >= 2);
	Assert::contains('Exception: Test error', $statements[1]);

	unset($_SERVER['HTTP_X_TRACY_AJAX'], $_COOKIE['tracy-webdriver']);
});


// AJAX exception without agent has no agent setup call
test('', function () {
	$_SERVER['HTTP_X_TRACY_AJAX'] = 'abcdef1234';
	unset($_COOKIE['tracy-webdriver']);

	$storage = new MockSessionStorage2;
	$defer = new Tracy\DeferredContent($storage);
	$defer->sendAssets();

	$bs = new Tracy\BlueScreen;
	$bs->renderToAjax(createTestException(), $defer);

	$code = $storage->data['setup']['abcdef1234']['code'] ?? '';
	Assert::contains('Tracy.BlueScreen.loadAjax(', $code);

	// Only one statement (loadAjax), no agent call
	$statements = array_filter(explode(";\n", $code));
	Assert::count(1, $statements);

	unset($_SERVER['HTTP_X_TRACY_AJAX']);
});
