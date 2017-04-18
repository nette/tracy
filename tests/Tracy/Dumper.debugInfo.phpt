<?php

/**
 * Test: Tracy\Dumper __debugInfo()
 */

use Tracy\Dumper;
use Tester\Assert;


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


Assert::match('Password #%a%
   password => "[censored]" (10)', Dumper::toText($obj));


Assert::match('Password #%a%
   password => "secret" (6)
   extra => "foo" (3)
', Dumper::toText($obj, [Dumper::DEBUGINFO => FALSE])
);


$container = new stdClass;
$container->passwordObject = $obj;


Assert::match('stdClass #%a%
   passwordObject => Password #%a%
   |  password => "[censored]" (10)
', Dumper::toText($container));


Assert::match('stdClass #%a%
   passwordObject => Password #%a%
   |  password => "secret" (6)
   |  extra => "foo" (3)
', Dumper::toText($container, [Dumper::DEBUGINFO => FALSE]));
