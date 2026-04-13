<?php declare(strict_types=1);

namespace Tracy;

/**
 * @var \Throwable $ex
 * @var callable $dump
 * @var BlueScreen $blueScreen
 */

[$stack, $expanded] = $blueScreen->prepareStack($ex);

$file = $ex->getFile();
$line = $ex->getLine();

require __DIR__ . '/../dist/section-stack-sourceFile.phtml';
require __DIR__ . '/../dist/section-stack-callStack.phtml';
