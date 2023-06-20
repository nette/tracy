<?php

declare(strict_types=1);

use Nette\Bridges\HttpDI\HttpExtension;
use Nette\Bridges\MailDI\MailExtension;
use Nette\DI;
use Tester\Assert;
use Tracy\Bridges\Nette\TracyExtension;

require __DIR__ . '/../bootstrap.php';


$compiler = new DI\Compiler;
$compiler->setClassName('Container');
$compiler->addExtension('tracy', new TracyExtension);
$compiler->addExtension('http', new HttpExtension);
$compiler->addExtension('mail', new MailExtension);

eval($compiler->compile());

Tracy\Debugger::enable();
$_SERVER['HTTP_HOST'] = 'foo';

$container = new Container;
$container->initialize();

$logger = $container->getService('tracy.logger');
Assert::type(Tracy\Logger::class, $container->getService('tracy.logger'));
$mailer = $logger->mailer[0];
Assert::type(Tracy\Bridges\Nette\MailSender::class, $mailer);

Assert::with($mailer, function () {
	Assert::same('foo', $this->host);
});
