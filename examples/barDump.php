<?php

require __DIR__ . '/../src/tracy.php';

use Tracy\Debugger;

// For security reasons, Tracy is visible only on localhost.
// You may force Tracy to run in development mode by passing the Debugger::DEVELOPMENT instead of Debugger::DETECT.
Debugger::enable(Debugger::DETECT, __DIR__ . '/log');

?>
<!DOCTYPE html><html class=arrow><link rel="stylesheet" href="assets/style.css">

<h1>Tracy: bar dump demo</h1>

<p>You can dump variables to bar in rightmost bottom egde.</p>

<style>
h1 { background: orange !important; }
h1:after {
content: "Look at this orange box.";
  background-color: #FFBA10;
  border-color: black;
  border-style: dotted;
}

#tracy-debug h1 {
	all: initial;
}

#tracy-debug *:after {
	all: unset;
}
</style>

<?php
$arr = [10, 20.2, true, null, 'hello', (object) null, []];

bdump(get_defined_vars());

bdump($arr, 'The Array');

bdump('<a href="#">test</a>', 'String');


if (Debugger::$productionMode) {
	echo '<p><b>For security reasons, Tracy is visible only on localhost. Look into the source code to see how to enable Tracy.</b></p>';
}
