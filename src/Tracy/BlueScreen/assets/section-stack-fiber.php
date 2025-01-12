<?php

declare(strict_types=1);

namespace Tracy;

/**
 * @var \Fiber $fiber
 * @var callable $dump
 */

$ref = new \ReflectionFiber($fiber);
$stack = $ref->getTrace();
$expanded = 0;

require __DIR__ . '/../dist/section-stack-callStack.phtml';
