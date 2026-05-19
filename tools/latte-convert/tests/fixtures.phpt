<?php declare(strict_types=1);

/**
 * Compiles each fixtures/*.latte and asserts the output matches the .phtml of the same name.
 * Fixtures whose name contains 'agent' are compiled in text (markdown) mode.
 */

use Tester\Assert;

require __DIR__ . '/bootstrap.php';

$engine = new Tracy\Tools\Compiler\Engine;

foreach (glob(__DIR__ . '/fixtures/*.latte') as $latteFile) {
	$name = basename($latteFile, '.latte');
	$phtmlFile = dirname($latteFile) . "/$name.phtml";

	test($name, function () use ($engine, $latteFile, $phtmlFile) {
		$textMode = str_contains($latteFile, 'agent');
		$engine->setContentType($textMode ? Latte\ContentType::Text : Latte\ContentType::Html);
		Assert::same(file_get_contents($phtmlFile), $engine->compile($latteFile));
	});
}
