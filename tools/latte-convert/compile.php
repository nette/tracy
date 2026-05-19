<?php declare(strict_types=1);

/**
 * Compiles .latte templates to standalone .phtml files using the real Latte engine.
 *
 * Usage:
 *   php compile.php <input.latte | directory>       single file or each *.latte in dir, output to sibling dist/
 *   php compile.php <input.latte> <output.phtml>    single file with explicit output
 *
 * Files whose basename contains "agent" are compiled in text (markdown) mode.
 */

require __DIR__ . '/vendor/autoload.php';

/** @var list<string> $args */
$args = $_SERVER['argv'] ?? [];

if (count($args) < 2) {
	fwrite(STDERR, "Usage: php compile.php <input.latte | directory> [<output.phtml>]\n");
	exit(1);
}

$engine = new Tracy\Tools\Compiler\Engine;
$path = $args[1];
if (is_file($path)) {
	compileFile($engine, $path, $args[2] ?? deriveOutput($path));
} elseif (is_dir($path)) {
	$iter = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path));
	foreach (new RegexIterator($iter, '~\.latte$~') as $file) {
		compileFile($engine, $file->getPathname(), deriveOutput($file->getPathname()));
	}
} else {
	fwrite(STDERR, "Not a file or directory: $path\n");
	exit(1);
}


function deriveOutput(string $latteFile): string
{
	$distDir = dirname($latteFile, 2) . '/dist';
	if (!is_dir($distDir)) {
		mkdir($distDir, recursive: true);
	}
	return $distDir . '/' . basename($latteFile, '.latte') . '.phtml';
}


function compileFile(Tracy\Tools\Compiler\Engine $engine, string $input, string $output): void
{
	$textMode = str_contains(basename($input), 'agent');
	$engine->setContentType($textMode ? Latte\ContentType::Text : Latte\ContentType::Html);
	if (file_put_contents($output, $engine->compile($input)) === false) {
		fwrite(STDERR, "Error writing to '$output'\n");
		exit(1);
	}
	echo "$input -> $output\n";
}
