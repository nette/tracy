<?php

declare(strict_types=1);

require __DIR__ . '/../src/tracy.php';

use Tracy\Debugger;

// For security reasons, Tracy is visible only on localhost.
// You may force Tracy to run in development mode by passing the Debugger::Development instead of Debugger::Detect.
Debugger::enable(Debugger::Detect, __DIR__ . '/log');

?>
<!DOCTYPE html><link rel="stylesheet" href="assets/style.css">

<h1>Tracy: dump() demo</h1>

<?php


echo "<h2>Basic Types</h2>\n";

dump('any string', 123, [true, false, null]);



echo "<h2>Dark Mode</h2>\n";

Debugger::$dumpTheme = 'dark';

dump('any string');



echo "<h2>Objects</h2>\n";
echo "<p>Hover over the name <code>\$baz</code> to see property declaring class and over the hash <code>#5</code> to see same objects.</p>\n";

enum Suit
{
	case Clubs;
	case Diamonds;
	case Hearts;
	case Spades;
}

class ParentClass
{
	public $foo = [10, 20];

	protected $bar = 30;

	private $baz = 'parent';

	public Suit $enum = Suit::Clubs;
}

#[\AllowDynamicProperties]
class ChildClass extends ParentClass
{
	private $baz = 'child';
}


$obj = new ChildClass;
$obj->dynamic = 'hello';
$obj->selfReference = $obj;

dump($obj);



echo "<h2>Strings</h2>\n";
echo "<p>Hover over the string to see length.</p>\n";

$arr = [
	'single line' => 'hello',
	'binary' => "binary\xA0string",
	'multi line' => "first\r\nsecond\nthird\n   indented line",
	'long' => str_repeat('tracy ', 1000), // affected by Tracy\Debugger::$maxLength
];

dump($arr);


echo "<h2>References and Recursion</h2>\n";
echo "<p>Hover over the reference <code>&1</code> to see referenced values.</p>\n";

$arr = ['first', 'second', 'third'];
$arr[] = &$arr[0];
$arr[] = &$arr[1];
$arr[] = &$arr;

dump($arr);


echo "<h2>Special Types</h2>\n";

$arr = [
	fopen(__FILE__, 'r'),
	new class {
},
	function ($x, $y) use (&$arr, $obj) {},
];

dump($arr);



if (Debugger::$productionMode) {
	echo '<p><b>For security reasons, Tracy is visible only on localhost. Look into the source code to see how to enable Tracy.</b></p>';
}
