<?php declare(strict_types=1);

/**
 * Test: Tracy\Logger exceptions.jsonl index & retention
 */

use Tester\Assert;
use Tracy\Logger;

require __DIR__ . '/../bootstrap.php';


test('exception is recorded in exceptions.jsonl', function () {
	$dir = getTempDir() . '/index';
	mkdir($dir);
	$logger = new Logger($dir);

	$logger->log(new RuntimeException('First error'), Logger::EXCEPTION);
	$logger->log(new LogicException('Second error'), Logger::ERROR);

	$lines = file($dir . '/exceptions.jsonl', FILE_IGNORE_NEW_LINES);
	Assert::count(2, $lines);

	$record = json_decode($lines[0], associative: true);
	Assert::same(Logger::EXCEPTION, $record['level']);
	Assert::contains('RuntimeException: First error', $record['message']);
	Assert::match('%h%', $record['hash']);
	Assert::match('exception--%a%--' . $record['hash'] . '.html', $record['file']);
	Assert::true(is_file($dir . '/' . $record['file']));

	$record = json_decode($lines[1], associative: true);
	Assert::same(Logger::ERROR, $record['level']);
	Assert::contains('LogicException: Second error', $record['message']);
});


test('old exception reports are purged by retention', function () {
	$dir = getTempDir() . '/retention';
	mkdir($dir);
	$logger = new Logger($dir);
	$logger->retention = '30 days';

	$old = $dir . '/exception--2020-01-01--12-00--abcdef1234.html';
	file_put_contents($old, 'old');
	touch($old, strtotime('-90 days'));
	file_put_contents(substr($old, 0, -5) . '.md', 'old');
	touch(substr($old, 0, -5) . '.md', strtotime('-90 days'));

	$logger->log(new RuntimeException('Fresh error'), Logger::EXCEPTION);

	Assert::false(is_file($old));
	Assert::false(is_file(substr($old, 0, -5) . '.md'));
	Assert::count(1, glob($dir . '/exception--*.html')); // the fresh report is kept
});


test('purge runs at most once a day', function () {
	$dir = getTempDir() . '/purge-marker';
	mkdir($dir);
	$logger = new Logger($dir);
	$logger->retention = '30 days';

	file_put_contents($dir . '/.tracy-purge', ''); // fresh marker

	$old = $dir . '/exception--2020-01-01--12-00--abcdef1234.html';
	file_put_contents($old, 'old');
	touch($old, strtotime('-90 days'));

	$logger->log(new RuntimeException('Another error'), Logger::EXCEPTION);
	Assert::true(is_file($old)); // purge skipped, marker is fresh
});


test('invalid retention is reported', function () {
	$dir = getTempDir() . '/invalid-retention';
	mkdir($dir);
	$logger = new Logger($dir);
	$logger->retention = 'nonsense';

	Assert::exception(
		fn() => $logger->log(new RuntimeException('Boom'), Logger::EXCEPTION),
		InvalidArgumentException::class,
		"Invalid time interval 'nonsense'.",
	);
});
