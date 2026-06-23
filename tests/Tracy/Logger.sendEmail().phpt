<?php declare(strict_types=1);

/**
 * Test: Tracy\Logger email sending & snooze
 */

use Tester\Assert;
use Tracy\Logger;

require __DIR__ . '/../bootstrap.php';


test('email is sent for first error and snoozed for the next one', function () {
	$logger = new Logger(getTempDir());
	$logger->email = 'admin@example.com';
	$sent = [];
	$logger->mailer = function ($message, $email) use (&$sent) {
		$sent[] = $message;
	};

	$logger->log('first', Logger::ERROR);
	$logger->log('second', Logger::ERROR);
	Assert::same(['first'], $sent);
	Assert::true(is_file(getTempDir() . '/email-sent'));
});


test('failed sending is not marked as sent', function () {
	$logger = new Logger(getTempDir() . '/fail');
	mkdir(getTempDir() . '/fail');
	$logger->email = 'admin@example.com';
	$calls = 0;
	$logger->mailer = function () use (&$calls) {
		$calls++;
		throw new RuntimeException('SMTP down');
	};

	Assert::exception(fn() => $logger->log('first', Logger::ERROR), RuntimeException::class, 'SMTP down');
	Assert::exception(fn() => $logger->log('second', Logger::ERROR), RuntimeException::class, 'SMTP down');
	Assert::same(2, $calls); // next error retries, notification is not lost

	$logger->mailer = function () use (&$calls) {
		$calls++;
	};
	$logger->log('third', Logger::ERROR);
	$logger->log('fourth', Logger::ERROR);
	Assert::same(3, $calls); // sent once, then snoozed
});


test('invalid emailSnooze is reported', function () {
	$logger = new Logger(getTempDir() . '/invalid');
	mkdir(getTempDir() . '/invalid');
	$logger->email = 'admin@example.com';
	$logger->emailSnooze = 'nonsense';
	$logger->mailer = function () {};

	Assert::exception(
		fn() => $logger->log('boom', Logger::ERROR),
		InvalidArgumentException::class,
		"Invalid time interval 'nonsense'.",
	);
});
