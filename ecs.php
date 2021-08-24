<?php

/**
 * Rules for Nette Coding Standard
 * https://github.com/nette/coding-standard
 */

declare(strict_types=1);


return function (Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator $containerConfigurator): void {
	$containerConfigurator->import(PRESET_DIR . '/php71.php');

	$parameters = $containerConfigurator->parameters();

	$parameters->set('skip', [
		'tmp/*',
		'fixtures*/*',
		'tests/Tracy/Dumper.toText().specials.enum.phpt', // enum

		PhpCsFixer\Fixer\ClassNotation\OrderedClassElementsFixer::class => [
			'src/Tracy/Debugger/Debugger.php',
		],

		// dump()
		Drew\DebugStatementsFixers\Dump::class => [
			'tests/Tracy/dump().cli.phpt',
			'tests/Tracy/dump().html.phpt',
			'tests/Tracy/dump().text.phpt',
		],
	]);
};
