<?php

/**
 * Test: Nette\Debug::tryError() & catchError.
 *
 * @author     David Grudl
 * @package    Nette
 * @subpackage UnitTests
 */

use Nette\Debug;



require __DIR__ . '/../initialize.php';



Debug::tryError(); {
	$a++;
} $res = Debug::catchError($message);

Assert::true( $res );
Assert::same( "Undefined variable: a", $message );



Debug::tryError(); {

} $res = Debug::catchError($message);

Assert::false( $res );
Assert::null( $message );
