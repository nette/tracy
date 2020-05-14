<?php

declare(strict_types=1);

require __DIR__ . '/../src/tracy.php';

use Tracy\Dumper;

?>
<!DOCTYPE html><link rel="stylesheet" href="assets/style.css">

<h1>Tracy: Dumper demo</h1>

<?php

// options:
// Dumper::$maxDepth = 7; // how many nested levels of array/object properties display
// Dumper::$maxLength = 150; // how long strings display
// Dumper::$maxItems = 100; // how many items in array/object display
// Dumper::$keysToHide = []; // sensitive keys not displayed
// Dumper::$theme = 'dark'; // theme light or dark


class Test
{
	public $x = [10, null];

	protected $z = 30;

	private $y = 'hello';
}

$arr = [10, 20.2, true, null, 'hello', (object) null, [], fopen(__FILE__, 'r')];

dump($arr);


echo "<h2>With location</h2>\n";

Dumper::$showLocation = true;

dump($arr);


echo "<h2>Dark theme</h2>\n";

Dumper::$theme = 'dark';

$obj = new Test;

dump($obj);
