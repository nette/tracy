<?php

/**
 * Test: Nette\Debug::dump() basic types in HTML and text mode.
 *
 * @author     David Grudl
 * @category   Nette
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


T::note("HTML mode");

Debug::$consoleMode = FALSE;

Debug::dump(NULL);

Debug::dump(TRUE);

Debug::dump(FALSE);

Debug::dump(0);

Debug::dump(1);

Debug::dump(0.0);

Debug::dump(0.1);

Debug::dump('');

Debug::dump('0');

Debug::dump("\x00");

Debug::dump(array(1, 'hello', array(), array(1, 2), array(1 => 1, 2)));

Debug::dump(fopen(__FILE__, 'r'));

Debug::dump((object) NULL);

$obj = new Test;
$res = Debug::dump($obj);

Assert::same($res,  $obj );


T::note("\nText mode");

Debug::$consoleMode = TRUE;

Debug::dump(NULL);

Debug::dump(TRUE);

Debug::dump(FALSE);

Debug::dump(0);

Debug::dump(1);

Debug::dump(0.0);

Debug::dump(0.1);

Debug::dump('');

Debug::dump('0');

Debug::dump("\x00");

Debug::dump(array(1, 'hello', array(), array(1, 2), array(1 => 1, 2)));

Debug::dump(fopen(__FILE__, 'r'));

Debug::dump((object) NULL);

$res = Debug::dump($obj);



__halt_compiler() ?>

------EXPECT------
HTML mode

<pre class="nette-dump">NULL
</pre>
<pre class="nette-dump">TRUE
</pre>
<pre class="nette-dump">FALSE
</pre>
<pre class="nette-dump">0
</pre>
<pre class="nette-dump">1
</pre>
<pre class="nette-dump">0.0
</pre>
<pre class="nette-dump">0.1
</pre>
<pre class="nette-dump">""
</pre>
<pre class="nette-dump">"0"
</pre>
<pre class="nette-dump">"\x00"
</pre>
<pre class="nette-dump"><span>array</span>(5) <code>[
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
<pre class="nette-dump"><span>stream resource</span>
</pre>
<pre class="nette-dump"><span>stdClass</span>(0)
</pre>
<pre class="nette-dump"><span>Test</span>(3) <code>{
   "x" => <span>array</span>(2) <code>[
      0 => 10
      1 => NULL
   ]</code>
   "y" <span>private</span> => "hello" (5)
   "z" <span>protected</span> => 30
}</code>
</pre>

Text mode

NULL

TRUE

FALSE

0

1

0.0

0.1

""

"0"

"\x00"

array(5) [
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

stream resource

stdClass(0)

Test(3) {
   "x" => array(2) [
      0 => 10
      1 => NULL
   ]
   "y" private => "hello" (5)
   "z" protected => 30
}
