<?php

declare(strict_types=1);

namespace Tracy;

use function in_array;

/**
 * @var \Throwable $ex
 * @var callable $dump
 * @var BlueScreen $blueScreen
 */

$stack = BlueScreen::cleanStackTrace($ex->getTrace());

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
