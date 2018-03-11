<?php

declare(strict_types=1);

require __DIR__ . '/../src/tracy.php';

use Tracy\Debugger;

// For security reasons, Tracy is visible only on localhost.
// You may force Tracy to run in development mode by passing the Debugger::DEVELOPMENT instead of Debugger::DETECT.
Debugger::enable(Debugger::DETECT, __DIR__ . '/log');

?>
<!DOCTYPE html><link rel="stylesheet" href="assets/style.css">

<h1>Tracy: Dumper demo</h1>

<?php

class Test
{
	public $x = [10, null];

	protected $z = 30;

	private $y = 'hello';
}

$arr = [10, 20.2, true, null, 'hello', (object) null, [], fopen(__FILE__, 'r')];

$obj = new Test;


dump('<a href="#">test</a>');

dump($arr);

dump($obj);


echo "<h2>With location</h2>\n";

Debugger::$showLocation = true;

dump($arr);


if (Debugger::$productionMode) {
	echo '<p><b>For security reasons, Tracy is visible only on localhost. Look into the source code to see how to enable Tracy.</b></p>';
}
