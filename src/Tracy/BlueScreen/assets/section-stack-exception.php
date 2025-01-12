<?php declare(strict_types=1);

namespace Tracy;

use function in_array;

/**
 * @var \Throwable $ex
 * @var callable $dump
 * @var BlueScreen $blueScreen
 */

$stack = $ex->getTrace();
if (in_array($stack[0]['class'] ?? null, [DevelopmentStrategy::class, ProductionStrategy::class], true)) {
	array_shift($stack);
}
if (
	($stack[0]['class'] ?? null) === Debugger::class
	&& in_array($stack[0]['function'], ['shutdownHandler', 'errorHandler'], true)
) {
	array_shift($stack);
}

$expanded = null;
if (
	(!$ex instanceof \ErrorException || in_array($ex->getSeverity(), [E_USER_NOTICE, E_USER_WARNING, E_USER_DEPRECATED], true))
	&& $blueScreen->isCollapsed($ex->getFile())
) {
	foreach ($stack as $key => $row) {
		if (isset($row['file']) && !$blueScreen->isCollapsed($row['file'])) {
			$expanded = $key;
			break;
		}
	}
}

$file = $ex->getFile();
$line = $ex->getLine();

require __DIR__ . '/../dist/section-stack-sourceFile.phtml';
require __DIR__ . '/../dist/section-stack-callStack.phtml';
