<?php

declare(strict_types=1);

use Nette\DI;
use Tester\Assert;
use Tracy\Bridges\Nette\TracyExtension;
use Tracy\ILogger;


require __DIR__ . '/../bootstrap.php';

class CustomLogger implements ILogger
{
	public function log($value, $priority = self::INFO)
	{
	}
}


$compiler = new DI\Compiler;
$compiler->setClassName('Container');
$compiler->addExtension('tracy', new TracyExtension);
$compiler->addConfig([
	'tracy' => [
		'logSeverity' => 'E_USER_WARNING',
		'keysToHide' => ['abc'],
	],
	'services' => [
		'tracy.logger' => 'CustomLogger',
	],
]);

eval($compiler->compile());

Tracy\Debugger::enable();

$container = new Container;
$container->initialize();

Assert::type('CustomLogger', $container->getService('tracy.logger'));
Assert::type('Tracy\BlueScreen', $container->getService('tracy.blueScreen'));
Assert::type('Tracy\Bar', $container->getService('tracy.bar'));

Assert::same(Tracy\Debugger::getLogger(), $container->getService('tracy.logger'));
Assert::same(Tracy\Debugger::getBlueScreen(), $container->getService('tracy.blueScreen'));
Assert::same(Tracy\Debugger::getBar(), $container->getService('tracy.bar'));

Assert::same(E_USER_WARNING, Tracy\Debugger::$logSeverity);
Assert::contains('password', Tracy\Debugger::getBlueScreen()->keysToHide);
Assert::contains('abc', Tracy\Debugger::getBlueScreen()->keysToHide);
