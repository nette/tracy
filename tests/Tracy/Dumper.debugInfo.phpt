<?php

/**
 * Test: Tracy\Dumper __debugInfo()
 */

declare(strict_types=1);

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


Assert::match(<<<'XX'
Password #%d%
   password: "[censored]" (10)
XX
, Dumper::toText($obj, [Dumper::DEBUGINFO => true]));


Assert::match(<<<'XX'
Password #%d%
   password: "secret" (6)
   extra: "foo" (3)
XX
, Dumper::toText($obj)
);


$container = new stdClass;
$container->passwordObject = $obj;


Assert::match(<<<'XX'
stdClass #%d%
   passwordObject: Password #%d%
   |  password: "[censored]" (10)
XX
, Dumper::toText($container, [Dumper::DEBUGINFO => true]));


Assert::match(<<<'XX'
stdClass #%d%
   passwordObject: Password #%d%
   |  password: "secret" (6)
   |  extra: "foo" (3)
XX
, Dumper::toText($container));
