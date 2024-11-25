<?php

declare(strict_types=1);

require __DIR__ . '/../src/tracy.php';

use Tracy\Debugger;

// For security reasons, Tracy is visible only on localhost.
// You may force Tracy to run in development mode by passing the Debugger::Development instead of Debugger::Detect.
Debugger::enable(Debugger::Detect, __DIR__ . '/log');

?>
<!DOCTYPE html><link rel="stylesheet" href="assets/style.css">

<h1>Tracy: Lazy Objects</h1>

<?php

class LazyClass
{
	public function __construct(
		public int $id,
		public string $title,
	) {
	}
}

$rc = new ReflectionClass(LazyClass::class);
$obj = $rc->newLazyGhost(function ($obj) {
	$obj->__construct(123, 'hello world');
});

$rc->getProperty('id')->setRawValueWithoutLazyInitialization($obj, 123);


echo "<h2>Lazy Ghost</h2>\n";

dump($obj);


echo "<h2>Initialized Object</h2>\n";

// Triggers initialization
$foo = $obj->title;

dump($obj);


if (Debugger::$productionMode) {
	echo '<p><b>For security reasons, Tracy is visible only on localhost. Look into the source code to see how to enable Tracy.</b></p>';
}
