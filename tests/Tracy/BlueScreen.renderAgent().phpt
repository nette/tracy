<?php declare(strict_types=1);

/**
 * Test: Tracy\BlueScreen::renderAsText()
 */

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


function createException(string $message, int $code = 0, ?Throwable $prev = null): Throwable
{
	return new Exception($message, $code, $prev);
}


$blueScreen = new Tracy\BlueScreen;


test('no error code when zero', function () use ($blueScreen) {
	$output = $blueScreen->renderAgent(createException('Oops'));
	Assert::match("%A%# Exception: Oops\n\nin %A%", $output);
});


test('stack trace section with arguments', function () use ($blueScreen) {
	$fn = function (string $name, int $count) {
		throw new RuntimeException('Deep error');
	};
	try {
		$fn('hello', 42);
	} catch (RuntimeException $e) {
		$output = $blueScreen->renderAgent($e);
	}

	Assert::match("%A%## Stack Trace%A%#0 = 'hello'%A%#1 = 42%A%", $output);
});


test('caused by section for chained exceptions', function () use ($blueScreen) {
	$prev = new InvalidArgumentException('Root cause', 7);
	$exception = new RuntimeException('Wrapper', 5, $prev);
	$output = $blueScreen->renderAgent($exception);

	Assert::match('%A%# RuntimeException: Wrapper #5%A%## Caused by: InvalidArgumentException: Root cause #7%A%', $output);
});


test('custom panel text key is included in agent output', function () {
	$blueScreen = new Tracy\BlueScreen;
	$blueScreen->addPanel(fn(?Throwable $e) => $e ? [
		'tab' => 'Database',
		'panel' => '<pre>SELECT 1</pre>',
		'text' => "SELECT 1\nSELECT 2",
	] : null);
	$blueScreen->addPanel(fn(?Throwable $e) => $e ? [
		'tab' => 'HTML only',
		'panel' => '<b>no text key</b>',
	] : null);

	$output = $blueScreen->renderAgent(new Exception('Oops'));
	Assert::match("%A%## Database\n\nSELECT 1\nSELECT 2%A%", $output);
	// dresscode:ignore literals.concatenatedLiterals.sameLine -- the needle is split to avoid matching this line in the stack trace
	Assert::notContains('## HTML' . ' only', $output); // panel without 'text' key gets no section
});


test('throwing panel does not break agent output', function () {
	$blueScreen = new Tracy\BlueScreen;
	$blueScreen->addPanel(function (?Throwable $e): array {
		throw new LogicException('broken panel');
	});

	$output = $blueScreen->renderAgent(new Exception('Oops'));
	Assert::match('%A%# Exception: Oops%A%', $output);
});


test('environment section hidden when disabled', function () {
	$bs = new Tracy\BlueScreen;
	$bs->showEnvironment = false;
	$output = $bs->renderAgent(createException('no-env-test'));
	$foo = '##'; // so that it does not appear in the code
	$stripped = preg_replace('/```.*?```/s', '', $output);
	Assert::notContains("$foo Environment", $stripped);
});


test('sensitive regions are hidden in source', function () use ($blueScreen) {
	$file = getTempDir() . '/sensitive.php';
	$secret = bin2hex(random_bytes(8));
	file_put_contents($file, "<?php\n\$password = /*sensitive{*/'$secret'/*}*/;\nreturn new Exception('Connection failed');\n");
	$output = $blueScreen->renderAgent(require $file);
	Assert::notContains($secret, $output);
	Assert::contains('$password = *****;', $output);
});


test('ErrorException uses severity name', function () use ($blueScreen) {
	$output = $blueScreen->renderAgent(new ErrorException('Bad value', 0, E_WARNING));
	Assert::match('%A%# Warning: Bad value%A%', $output);
});


test('exception with custom properties', function () use ($blueScreen) {
	$e = new class ('DB error') extends RuntimeException {
		public string $query = 'SELECT * FROM users';
	};
	$output = $blueScreen->renderAgent($e);
	$foo = '##'; // so that it does not appear in the code
	Assert::match("%A%$foo Exception Properties%A%SELECT * FROM users%A%", $output);
});


test('no properties section for standard exception', function () use ($blueScreen) {
	$output = $blueScreen->renderAgent(createException('plain'));
	$foo = '##'; // so that it does not appear in the code
	Assert::notContains("$foo Exception Properties", $output);
});
