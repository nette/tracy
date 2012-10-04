<?php

/**
 * Test: Nette\Diagnostics\Debugger::dump() basic types in HTML and text mode.
 *
 * @author     David Grudl
 * @package    Nette\Diagnostics
 */

use Nette\Diagnostics\Debugger;



require __DIR__ . '/../bootstrap.php';



Debugger::$consoleColors = NULL;
Debugger::$productionMode = FALSE;



class Test
{
	public $x = array(10, NULL);

	private $y = 'hello';

	protected $z = 30;
}


// HTML mode

Debugger::$consoleMode = FALSE;

Assert::match( '<pre class="nette-dump"><span class="nette-dump-null">NULL</span>
</pre>', Debugger::dump(NULL, TRUE) );

Assert::match( '<pre class="nette-dump"><span class="nette-dump-bool">TRUE</span>
</pre>', Debugger::dump(TRUE, TRUE) );

Assert::match( '<pre class="nette-dump"><span class="nette-dump-bool">FALSE</span>
</pre>', Debugger::dump(FALSE, TRUE) );

Assert::match( '<pre class="nette-dump"><span class="nette-dump-int">0</span>
</pre>', Debugger::dump(0, TRUE) );

Assert::match( '<pre class="nette-dump"><span class="nette-dump-int">1</span>
</pre>', Debugger::dump(1, TRUE) );

Assert::match( '<pre class="nette-dump"><span class="nette-dump-float">0.0</span>
</pre>', Debugger::dump(0.0, TRUE) );

Assert::match( '<pre class="nette-dump"><span class="nette-dump-float">0.1</span>
</pre>', Debugger::dump(0.1, TRUE) );

Assert::match( '<pre class="nette-dump"><span class="nette-dump-string">""</span>
</pre>', Debugger::dump('', TRUE) );

Assert::match( '<pre class="nette-dump"><span class="nette-dump-string">"0"</span>
</pre>', Debugger::dump('0', TRUE) );

Assert::match( '<pre class="nette-dump"><span class="nette-dump-string">"\\x00"</span>
</pre>', Debugger::dump("\x00", TRUE) );

Assert::match( '<pre class="nette-dump"><span class="nette-toggle"><span class="nette-dump-array">array</span>(5)</span> <code>[
   <span class="nette-dump-key">0</span> => <span class="nette-dump-int">1</span>
   <span class="nette-dump-key">1</span> => <span class="nette-dump-string">"hello"</span> (5)
   <span class="nette-dump-key">2</span> => <span class="nette-dump-array">array</span>(0)
   <span class="nette-dump-key">3</span> => <span class="nette-toggle"><span class="nette-dump-array">array</span>(2)</span> <code>[
      <span class="nette-dump-key">0</span> => <span class="nette-dump-int">1</span>
      <span class="nette-dump-key">1</span> => <span class="nette-dump-int">2</span>
   ]</code>
   <span class="nette-dump-key">4</span> => <span class="nette-toggle"><span class="nette-dump-array">array</span>(2)</span> <code>{
      <span class="nette-dump-key">1</span> => <span class="nette-dump-int">1</span>
      <span class="nette-dump-key">2</span> => <span class="nette-dump-int">2</span>
   }</code>
]</code>
</pre>
', Debugger::dump(array(1, 'hello', array(), array(1, 2), array(1 => 1, 2)), TRUE) );

Assert::match( '<pre class="nette-dump"><span class="nette-toggle-collapsed"><span class="nette-dump-resource">stream resource</span></span> <code class="nette-collapsed">{%A%}</code>
</pre>', Debugger::dump(fopen(__FILE__, 'r'), TRUE) );

Assert::match( '<pre class="nette-dump"><span class="nette-dump-object">stdClass</span>(0)
</pre>', Debugger::dump((object) NULL, TRUE) );

$obj = new Test;
Assert::same(Debugger::dump($obj), $obj);


// Text mode

Debugger::$consoleMode = TRUE;

Assert::match( 'NULL', Debugger::dump(NULL, TRUE) );

Assert::match( 'TRUE', Debugger::dump(TRUE, TRUE) );

Assert::match( 'FALSE', Debugger::dump(FALSE, TRUE) );

Assert::match( '0', Debugger::dump(0, TRUE) );

Assert::match( '1', Debugger::dump(1, TRUE) );

Assert::match( '0.0', Debugger::dump(0.0, TRUE) );

Assert::match( '0.1', Debugger::dump(0.1, TRUE) );

Assert::match( '""', Debugger::dump('', TRUE) );

Assert::match( '"0"', Debugger::dump('0', TRUE) );

Assert::match( '"\\x00"', Debugger::dump("\x00", TRUE) );

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
', Debugger::dump(array(1, 'hello', array(), array(1, 2), array(1 => 1, 2)), TRUE) );

Assert::match( 'stream resource {%A%}', Debugger::dump(fopen(__FILE__, 'r'), TRUE) );

Assert::match( 'stdClass(0)', Debugger::dump((object) NULL, TRUE) );

Assert::match( 'Test(3) {
   x => array(2) [
      0 => 10
      1 => NULL
   ]
   y private => "hello" (5)
   z protected => 30
}
', Debugger::dump($obj, TRUE) );
