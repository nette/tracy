<?php

/**
 * Test: Nette\Debug::dump() basic types in HTML and text mode.
 *
 * @author     David Grudl
 * @package    Nette
 * @subpackage UnitTests
 */

use Nette\Debug;



require __DIR__ . '/../initialize.php';



Debug::$productionMode = FALSE;



class Test
{
	public $x = array(10, NULL);

	private $y = 'hello';

	protected $z = 30;
}


// HTML mode

Debug::$consoleMode = FALSE;

Assert::match( '<pre class="nette-dump">NULL
</pre>', Debug::dump(NULL, TRUE) );

Assert::match( '<pre class="nette-dump">TRUE
</pre>', Debug::dump(TRUE, TRUE) );

Assert::match( '<pre class="nette-dump">FALSE
</pre>', Debug::dump(FALSE, TRUE) );

Assert::match( '<pre class="nette-dump">0
</pre>', Debug::dump(0, TRUE) );

Assert::match( '<pre class="nette-dump">1
</pre>', Debug::dump(1, TRUE) );

Assert::match( '<pre class="nette-dump">0.0
</pre>', Debug::dump(0.0, TRUE) );

Assert::match( '<pre class="nette-dump">0.1
</pre>', Debug::dump(0.1, TRUE) );

Assert::match( '<pre class="nette-dump">""
</pre>', Debug::dump('', TRUE) );

Assert::match( '<pre class="nette-dump">"0"
</pre>', Debug::dump('0', TRUE) );

Assert::match( '<pre class="nette-dump">"\\x00"
</pre>', Debug::dump("\x00", TRUE) );

Assert::match( '<pre class="nette-dump"><span>array</span>(5) <code>[
   0 => 1
   1 => "hello" (5)
   2 => <span>array</span>(0)
   3 => <span>array</span>(2) <code>[
      0 => 1
      1 => 2
   ]</code>
   4 => <span>array</span>(2) <code>{
      1 => 1
      2 => 2
   }</code>
]</code>
</pre>
', Debug::dump(array(1, 'hello', array(), array(1, 2), array(1 => 1, 2)), TRUE) );

Assert::match( '<pre class="nette-dump"><span>stream resource</span>
</pre>', Debug::dump(fopen(__FILE__, 'r'), TRUE) );

Assert::match( '<pre class="nette-dump"><span>stdClass</span>(0)
</pre>', Debug::dump((object) NULL, TRUE) );

$obj = new Test;
Assert::same(Debug::dump($obj), $obj);


// Text mode

Debug::$consoleMode = TRUE;

Assert::match( 'NULL', Debug::dump(NULL, TRUE) );

Assert::match( 'TRUE', Debug::dump(TRUE, TRUE) );

Assert::match( 'FALSE', Debug::dump(FALSE, TRUE) );

Assert::match( '0', Debug::dump(0, TRUE) );

Assert::match( '1', Debug::dump(1, TRUE) );

Assert::match( '0.0', Debug::dump(0.0, TRUE) );

Assert::match( '0.1', Debug::dump(0.1, TRUE) );

Assert::match( '""', Debug::dump('', TRUE) );

Assert::match( '"0"', Debug::dump('0', TRUE) );

Assert::match( '"\\x00"', Debug::dump("\x00", TRUE) );

Assert::match( 'array(5) [
   0 => 1
   1 => "hello" (5)
   2 => array(0)
   3 => array(2) [
      0 => 1
      1 => 2
   ]
   4 => array(2) {
      1 => 1
      2 => 2
   }
]
', Debug::dump(array(1, 'hello', array(), array(1, 2), array(1 => 1, 2)), TRUE) );

Assert::match( 'stream resource', Debug::dump(fopen(__FILE__, 'r'), TRUE) );

Assert::match( 'stdClass(0)', Debug::dump((object) NULL, TRUE) );

Assert::match( 'Test(3) {
   "x" => array(2) [
      0 => 10
      1 => NULL
   ]
   "y" private => "hello" (5)
   "z" protected => 30
}
', Debug::dump($obj, TRUE) );
