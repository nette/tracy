<?php declare(strict_types=1);

/**
 * Test: TracyExtension 'editor: false' disables editor links.
 */

use Nette\DI;
use Tester\Assert;
use Tracy\Bridges\Nette\TracyExtension;

require __DIR__ . '/../bootstrap.php';


$compiler = new DI\Compiler;
$compiler->setClassName('Container1');
$compiler->addExtension('tracy', new TracyExtension);
$compiler->addConfig([
	'tracy' => [
		'editor' => false,
	],
]);

eval($compiler->compile());

Tracy\Debugger::enable();

$container = new Container1;
$container->initialize();

Assert::null(Tracy\Debugger::$editor);
