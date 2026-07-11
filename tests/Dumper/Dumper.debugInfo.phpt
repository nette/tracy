<?php declare(strict_types=1);

/**
 * Test: Tracy\Dumper __debugInfo()
 */

use Tester\Assert;
use Tracy\Dumper;

require __DIR__ . '/../bootstrap.php';


class Password
{
	public $password;
	public $extra = 'foo';


	public function __debugInfo()
	{
		return [
			'password' => '[censored]',
		];
	}
}


$obj = new Password;
$obj->password = 'secret';


Assert::match(
	<<<'XX'
		Password #%d%
		   password: '[censored]'
		XX,
	Dumper::toText($obj, [Dumper::DEBUGINFO => true]),
);


Assert::match(
	<<<'XX'
		Password #%d%
		   password: 'secret'
		   extra: 'foo'
		XX,
	Dumper::toText($obj),
);


$container = new stdClass;
$container->passwordObject = $obj;


Assert::match(
	<<<'XX'
		stdClass #%d%
		   passwordObject: Password #%d%
		   |  password: '[censored]'
		XX,
	Dumper::toText($container, [Dumper::DEBUGINFO => true]),
);


Assert::match(
	<<<'XX'
		stdClass #%d%
		   passwordObject: Password #%d%
		   |  password: 'secret'
		   |  extra: 'foo'
		XX,
	Dumper::toText($container),
);


// KEYS_TO_HIDE is applied to keys returned by __debugInfo
Assert::match(
	<<<'XX'
		Password #%d%
		   password: ***** (string)
		XX,
	Dumper::toText($obj, [Dumper::DEBUGINFO => true, Dumper::KEYS_TO_HIDE => ['password']]),
);


// KEYS_TO_HIDE is applied to keys returned by custom object exporters
Assert::match(
	<<<'XX'
		Password #%d%
		   apiKey: ***** (string)
		   name: 'joe'
		XX,
	Dumper::toText($obj, [
		Dumper::OBJECT_EXPORTERS => [Password::class => fn() => ['apiKey' => 'secret', 'name' => 'joe']],
		Dumper::KEYS_TO_HIDE => ['apiKey'],
	]),
);
