<?php

/**
 * Test: Nette\Debug::tryError() & catchError.
 *
 * @author     David Grudl
 * @category   Nette
 * @package    Nette
 * @subpackage UnitTests
 */

use Nette\Debug;



require __DIR__ . '/../initialize.php';



Debug::tryError(); {
	$a++;
} $res = Debug::catchError($message);

T::dump( $res );
T::dump( $message );



Debug::tryError(); {

} $res = Debug::catchError($message);

T::dump( $res );
T::dump( $message );



__halt_compiler() ?>

------EXPECT------
TRUE

"Undefined variable: a"

FALSE

NULL
