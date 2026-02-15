<?php declare(strict_types=1);

/**
 * Test: Tracy\Bar::renderAgent() with AJAX and redirect requests.
 */

use Tester\Assert;
use Tracy\Debugger;

require __DIR__ . '/../bootstrap.php';

if (PHP_SAPI === 'cli') {
	Tester\Environment::skip('Requires CGI mode');
}

Debugger::$productionMode = false;
Debugger::$time = microtime(true);

class MockSessionStorage implements Tracy\SessionStorage
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


function createDefer(MockSessionStorage $storage): Tracy\DeferredContent
{
	$defer = new Tracy\DeferredContent($storage);
	$defer->sendAssets(); // activates useSession
	return $defer;
}


// AJAX request includes console.log for agent
test('', function () {
	$_SERVER['HTTP_X_TRACY_AJAX'] = 'abcdef1234';
	$_COOKIE['tracy-webdriver'] = '1';
	setHtmlMode();

	$storage = new MockSessionStorage;
	$defer = createDefer($storage);

	$bar = new Tracy\Bar;
	$bar->render($defer);

	$code = $storage->data['setup']['abcdef1234']['code'] ?? '';
	Assert::match(
		"Tracy.Debug.loadAjax(%A%);\nconsole.log(\"Tracy Bar | %a% ms | %a% MB\\n\");\n",
		$code,
	);

	unset($_SERVER['HTTP_X_TRACY_AJAX']);
});


// AJAX request without agent has no console.log
test('', function () {
	$_SERVER['HTTP_X_TRACY_AJAX'] = 'abcdef1234';
	unset($_COOKIE['tracy-webdriver']);
	setHtmlMode();

	$storage = new MockSessionStorage;
	$defer = createDefer($storage);

	$bar = new Tracy\Bar;
	$bar->render($defer);

	$code = $storage->data['setup']['abcdef1234']['code'] ?? '';
	Assert::match("Tracy.Debug.loadAjax(%A%);\n", $code);
	Assert::notContains('console.log', $code);

	unset($_SERVER['HTTP_X_TRACY_AJAX']);
});


// redirect stores agent text in queue
test('', function () {
	unset($_SERVER['HTTP_X_TRACY_AJAX']);
	$_COOKIE['tracy-webdriver'] = '1';
	setHtmlMode();
	header('Location: /next');

	$storage = new MockSessionStorage;
	$defer = createDefer($storage);

	$bar = new Tracy\Bar;
	$bar->render($defer);

	$queue = $storage->data['redirect'] ?? [];
	Assert::count(1, $queue);
	Assert::match("Tracy Bar | %a% ms | %a% MB\n", $queue[0]['agent']);

	header_remove('Location');
	http_response_code(200);
});


// redirect agent text is output on next page
test('', function () {
	unset($_SERVER['HTTP_X_TRACY_AJAX']);
	$_COOKIE['tracy-webdriver'] = '1';
	setHtmlMode();
	header_remove('Location');

	$storage = new MockSessionStorage;
	$storage->data['redirect'] = [
		['content' => ['bar' => '', 'panels' => ''], 'agent' => "Tracy Bar | 10.0 ms | 5.00 MB\n\n## Warnings\n\n- test\n", 'time' => time()],
	];
	$defer = createDefer($storage);

	$bar = new Tracy\Bar;
	// Set loaderRendered to avoid removeOutputBuffers call
	ob_start();
	$bar->renderLoader($defer);
	ob_end_clean();

	ob_start();
	$bar->render($defer);
	$output = ob_get_clean();

	// Should contain two console.log calls: one from redirect queue, one from current request
	Assert::match('%A%console.log(%A%Warnings%A%)%A%console.log(%A%Tracy Bar |%A%)%A%', $output);
});
