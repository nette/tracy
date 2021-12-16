<?php

declare(strict_types=1);

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


$blueScreen = new Tracy\BlueScreen;

$render = function ($exception) use ($blueScreen) {
	ob_start();
	$blueScreen->render($exception);
	try {
		return ob_get_contents();
	} finally {
		ob_end_clean();
	}
};

$exception = new Exception('foo');

$lookFor = '<h2 class="section-label"><a href="#" data-tracy-ref="^+" class="tracy-toggle tracy-collapsed">Environment</a></h2>';

// sanity test: The environment section is present in the rendered string
$c = $render($exception);
Assert::true(strpos($c, $lookFor) !== false);

// on memory failures, it's hidden by default:
$c = $render($hohoh = new ErrorException('Fatal Error: Allowed memory size of 134217728 bytes exhausted'));
Assert::true(strpos($c, $lookFor) === false);

// this time the section is absent:
$blueScreen->showEnvironment = false;
$c = $render($exception);
Assert::true(strpos($c, $lookFor) === false);
