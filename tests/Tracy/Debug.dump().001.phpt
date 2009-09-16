<?php

/**
 * Test: Nette\Debug::dump() in HTML and text mode.
 *
 * @author     David Grudl
 * @category   Nette
 * @package    Nette
 * @subpackage UnitTests
 */

/*use Nette\Debug;*/



require dirname(__FILE__) . '/../NetteTest/initialize.php';



Debug::$consoleMode = FALSE;
Debug::$productionMode = FALSE;



class Test
{
	public $x = array(10, NULL);

	private $y = 'hello';

	protected $z = 30;
}

$arr = array(10, 20.2, TRUE, NULL, 'hello', (object) NULL, array());

$obj = new Test;


output("HTML mode");

Debug::$consoleMode = FALSE;

Debug::dump('<a href="#">test</a>');

Debug::dump("Special\x12chars");

Debug::dump($arr);

Debug::dump($obj);


output("\nText mode");

Debug::$consoleMode = TRUE;

Debug::dump('<a href="#">test</a>');

Debug::dump("Special\x12chars");

Debug::dump($arr);

$res = Debug::dump($obj);


dump( $res === $obj, 'result = var' );



__halt_compiler();

------EXPECT------
HTML mode

<pre class="dump"><span>string</span>(20) "&lt;a href="#"&gt;test&lt;/a&gt;"
</pre>
<pre class="dump"><span>string</span>(13) "Special\x12chars"
</pre>
<pre class="dump"><span>array</span>(7) <code>{
   0 => <span>int</span>(10)
   1 => <span>float</span>(20.2)
   2 => <span>bool</span>(TRUE)
   3 => <span>NULL</span>
   4 => <span>string</span>(5) "hello"
   5 => <span>object</span>(stdClass) (0) {}
   6 => <span>array</span>(0)
}</code>
</pre>
<pre class="dump"><span>object</span>(Test) (3) <code>{
   "x" => <span>array</span>(2) <code>{
      0 => <span>int</span>(10)
      1 => <span>NULL</span>
   }</code>
   "y" <span>private</span> => <span>string</span>(5) "hello"
   "z" <span>protected</span> => <span>int</span>(30)
}</code>
</pre>

Text mode

string(20) "<a href="#">test</a>"

string(13) "Special\x12chars"

array(7) {
   0 => int(10)
   1 => float(20.2)
   2 => bool(TRUE)
   3 => NULL
   4 => string(5) "hello"
   5 => object(stdClass) (0) {}
   6 => array(0)
}

object(Test) (3) {
   "x" => array(2) {
      0 => int(10)
      1 => NULL
   }
   "y" private => string(5) "hello"
   "z" protected => int(30)
}

result = var: bool(TRUE)
