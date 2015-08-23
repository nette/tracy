<?php

/**
 * Test: TracyExtension accessors.
 */

use Nette\DI;
use Tracy\Bridges\Nette\TracyExtension;
use Tester\Assert;


require __DIR__ . '/../bootstrap.php';


$compiler = new DI\Compiler;
$compiler->addExtension('tracy', new TracyExtension);

eval($compiler->compile([], 'Container1'));

$container = new Container1;
Assert::type(Tracy\Logger::class, $container->getService('tracy.logger'));
Assert::type(Tracy\BlueScreen::class, $container->getService('tracy.blueScreen'));
Assert::type(Tracy\Bar::class, $container->getService('tracy.bar'));

Assert::same(Tracy\Debugger::getLogger(), $container->getService('tracy.logger'));
Assert::same(Tracy\Debugger::getBlueScreen(), $container->getService('tracy.blueScreen'));
Assert::same(Tracy\Debugger::getBar(), $container->getService('tracy.bar'));
