<?php

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


Assert::match('Password #%a%
   password => "[censored]" (10)', Dumper::toText($obj, [Dumper::DEBUGINFO => true]));


Assert::match('Password #%a%
   password => "secret" (6)
   extra => "foo" (3)
', Dumper::toText($obj)
);


$container = new stdClass;
$container->passwordObject = $obj;


Assert::match('stdClass #%a%
   passwordObject => Password #%a%
   |  password => "[censored]" (10)
', Dumper::toText($container, [Dumper::DEBUGINFO => true]));


Assert::match('stdClass #%a%
   passwordObject => Password #%a%
   |  password => "secret" (6)
   |  extra => "foo" (3)
', Dumper::toText($container));
