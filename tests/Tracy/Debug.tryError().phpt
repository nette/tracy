<?php

/**
 * Test: Nette\Debug::tryError() & catchError.
 *
 * @author     David Grudl
 * @package    Nette
 * @subpackage UnitTests
 */

use Nette\Debug;



require __DIR__ . '/../bootstrap.php';



Debug::tryError(); {
	$a++;
} $res = Debug::catchError($e);

Assert::true( $res );
Assert::same( "Undefined variable: a", $e->getMessage() );



Debug::tryError(); {

} $res = Debug::catchError($e);

Assert::false( $res );
Assert::null( $e );
