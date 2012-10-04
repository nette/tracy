<?php

/**
 * Test: Nette\Diagnostics\Dump::toText()
 *
 * @author     David Grudl
 * @package    Nette\Diagnostics
 */

use Nette\Diagnostics\Dump;



require __DIR__ . '/../bootstrap.php';



class Test
{
	public $x = array(10, NULL);

	private $y = 'hello';

	protected $z = 30.0;
}


Assert::match( 'NULL', Dump::toText(NULL) );

Assert::match( 'TRUE', Dump::toText(TRUE) );

Assert::match( 'FALSE', Dump::toText(FALSE) );

Assert::match( '0', Dump::toText(0) );

Assert::match( '1', Dump::toText(1) );

Assert::match( '0.0', Dump::toText(0.0) );

Assert::match( '0.1', Dump::toText(0.1) );

Assert::match( '""', Dump::toText('') );

Assert::match( '"0"', Dump::toText('0') );

Assert::match( '"\\x00"', Dump::toText("\x00") );

Assert::match( 'array (5) [
   0 => 1
   1 => "hello" (5)
   2 => array (0)
   3 => array (2) [
      0 => 1
      1 => 2
   ]
   4 => array (7) {
      1 => 1
      2 => 2
      3 => 3
      4 => 4
      5 => 5
      6 => 6
      7 => 7
   }
]
', Dump::toText(array(1, 'hello', array(), array(1, 2), array(1 => 1, 2, 3, 4, 5, 6, 7))) );

Assert::match( 'stream resource {%A%}', Dump::toText(fopen(__FILE__, 'r')) );

Assert::match( 'stdClass (0)', Dump::toText((object) NULL) );

Assert::match( 'Test (3) {
   x => array (2) [
      0 => 10
      1 => NULL
   ]
   y private => "hello" (5)
   z protected => 30.0
}
', Dump::toText(new Test) );
