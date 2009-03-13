<h1>Nette\Debug recoverable error test</h1>


<pre>
<?php
require_once '../../Nette/loader.php';

/*use Nette\Debug;*/

Debug::$consoleMode = FALSE;
Debug::enable();


class TestClass
{

	function test1(array $val)
	{
	}


	function test2(TestClass $val)
	{
	}


	function __toString()
	{
		return FALSE;
	}


}


$obj = new TestClass;

try {
	echo "Invalid argument #1\n";
	$obj->test1('hello');
} catch (Exception $e) {
	echo "$e\n\n";
}

try {
	echo "Invalid argument #2\n";
	$obj->test2('hello');
} catch (Exception $e) {
	echo "$e\n\n";
}

try {
	echo "Invalid toString\n";
	echo $obj;
} catch (Exception $e) {
	echo "$e\n\n";
}
